<?php
namespace Craft;

class SproutImport_ElementService extends BaseApplicationComponent
{
	private $type;

	private $element;

	/**
	 * Elements saved during the length of the import job
	 *
	 * @var array
	 */
	protected $savedElements = array();

	/**
	 * Element Ids saved during the length of the import job
	 *
	 * @var array
	 */
	protected $savedElementIds = array();

	/**
	 * @var array
	 */
	protected $unsavedElements = array();

	/**
	 * @var array
	 */
	protected $updatedElements = array();

	/**
	 * @param array $elements
	 * @param bool  $returnSavedElementIds
	 *
	 * @return array
	 * @throws Exception
	 * @throws \CDbException
	 * @throws \Exception
	 */
	public function saveElement(array $element, $seed = false, $source = null)
	{
		$type = sproutImport()->getImporterModel($element);

		// Adds extra element keys to pass validation
		$importerClass = sproutImport()->getImporterByModelRow($type, $element);

		$importerElementKeys = $importerClass->defineKeys();

		$elementKeys = array_merge($this->getElementKeys(), $importerElementKeys);

		$inputKeys   = array_keys($element);

		// Catches invalid element keys
		$elementDiff = array_diff($inputKeys, $elementKeys);

		if (!empty($elementDiff))
		{
			$inputKeysText = implode(', ', $elementDiff);

			$msg = Craft::t("Invalid element keys $inputKeysText.");

			sproutImport()->addError($msg, $inputKeysText);

			return false;
		}



		$this->element = $element;
		$this->type    = $type;

		$beforeSave = sproutImport()->getValueByKey('content.beforeSave', $element);

		$model = $this->getElementModel($beforeSave);

		$content    = sproutImport()->getValueByKey('content', $element);
		$fields     = sproutImport()->getValueByKey('content.fields', $element);
		$related    = sproutImport()->getValueByKey('content.related', $element);
		$attributes = sproutImport()->getValueByKey('attributes', $element);


		if (!empty($fields))
		{
			// Catches invalid field handles stops the importing
			$elementFieldHandles = sproutImport()->getAllFieldHandles($type);

			// Merge default handle
			$elementFieldHandles[] = 'title';

			foreach ($fields as $fieldHandle => $fieldValue)
			{
				if (!in_array($fieldHandle, $elementFieldHandles))
				{
					$key = 'field-null-' . $fieldHandle;

					$msg = Craft::t("Could not find the $fieldHandle field.");

					sproutImport()->addError($msg, $key);

					return false;
				}
			}
		}

		// Allows author email to add as author of the entry
		if (isset($attributes['authorId']))
		{
			if (is_array($attributes['authorId']) && !empty($attributes['authorId']['email']))
			{
				$userEmail = $attributes['authorId']['email'];
				$userModel = craft()->users->getUserByUsernameOrEmail($userEmail);

				if ($userModel != null)
				{
					$authorId               = $userModel->getAttribute('id');
					$attributes['authorId'] = $authorId;
				}
			}
			else
			{
				$userModel = craft()->users->getUserById($attributes['authorId']);
			}

			if ($userModel == null)
			{
				$msg = Craft::t("Invalid author value");

				sproutImport()->addError($msg, 'invalid-author');

				return false;
			}
		}

		if (!empty($fields))
		{
			$fields = $this->resolveMatrixRelationships($fields);

			if (!$fields)
			{
				return false;
			}
		}

		// @todo - when trying to import Sprout Forms Form Models,
		// which do not have any fields or content, running this method kills the script
		// moving the $related check to before the method runs, works.
		if (count($related))
		{
			$fields = $this->resolveRelationships($related, $fields);

			if (!$fields)
			{
				return false;
			}
		}

		$model->setAttributes($attributes);
		unset($element['content']['related']);

		$model->setContent($content);
		$model->setContentFromPost($fields);

		$eventParams = array('element' => $model,
		                     'seed'    => $seed,
		                     '@model'  => $type,
		                     'source'  => $source
												);

		$event = new Event($this, $eventParams);

		sproutImport()->onBeforeMigrateElement($event);

		sproutImport()->log("Begin validation of Element Model.");

		$saved = false;
		if ($model->validate())
		{
			$isNewElement = !$model->id;

			try
			{
				$importer = sproutImport()->getImporterByRow($element);

				$importer->setData($element);

				$importer->setModel($model);

				$saved = $importer->save();

				if ($saved)
				{
					$importer->resolveNestedSettings($model, $element);
				}

				if ($saved && $isNewElement)
				{
					$this->savedElementIds[] = $model->id;
					$this->savedElements[]   = $model->getTitle();
				}
				elseif ($saved && !$isNewElement)
				{
					$this->updatedElements[] = $model->getTitle();
				}
				else
				{
					$this->unsavedElements[] = $model->getTitle();
				}

				// @todo Ask Dale why the following is necessary
				// Assign user to created groups
				if (strtolower($type) == 'user' && !empty($attributes['groupId']))
				{
					$groupIds = $attributes['groupId'];
					craft()->userGroups->assignUserToGroups($model->id, $groupIds);
				}
			}
			catch (\Exception $e)
			{
				$this->unsavedElements[] = array('title' => $model->getTitle(), 'error' => $e->getMessage());
				$title                   = sproutImport()->getValueByKey('content.title', $element);
				$msg                     = $title . ' ' . implode(', ', array_keys($fields)) . ' Check field values if it exists.';
				sproutImport()->error($msg);

				sproutImport()->error($e->getMessage());
			}
		}
		else
		{
			$this->unsavedElements[] = array(
				'title' => $model->getTitle(),
				'error' => print_r($model->getErrors(), true)
			);

			sproutImport()->addError($model->getErrors(), 'model-validate');
		}

		if ($saved)
		{
			// Pass the updated model after save
			$eventParams['element'] = $model;

			$event = new Event($this, $eventParams);

			sproutImport()->onAfterMigrateElement($event);

			return $model->id;
		}
	}

	/**
	 * @param bool $returnSavedElementIds
	 *
	 * @return array
	 */
	public function getSavedResults($returnSavedElementIds = false)
	{
		$result = array(
			'saved'          => count($this->savedElements),
			'updated'        => count($this->updatedElements),
			'unsaved'        => count($this->unsavedElements),
			'unsavedDetails' => $this->unsavedElements,
		);

		return $returnSavedElementIds ? $this->savedElementIds : $result;
	}

	/**
	 * Allows us to resolve relationships at the matrix field level
	 *
	 * @param $fields
	 */
	public function resolveMatrixRelationships($fields)
	{
		foreach ($fields as $field => $blocks)
		{
			if (is_array($blocks) && count($blocks))
			{
				foreach ($blocks as $block => $attributes)
				{
					if (strpos($block, 'new') === 0 && isset($attributes['related']))
					{
						$blockFields   = isset($attributes['fields']) ? $attributes['fields'] : array();
						$relatedFields = $attributes['related'];

						$blockFields = $this->resolveRelationships($relatedFields, $blockFields);

						if (!$blockFields)
						{
							return false;
						}

						unset($fields[$field][$block]['related']);

						if (empty($blockFields))
						{
							unset($fields[$field][$block]);
						}
						else
						{
							$fields[$field][$block]['fields'] = array_unique($blockFields);
						}
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * @param array $related
	 * @param array $fields
	 *
	 * @throws Exception
	 */
	protected function resolveRelationships(array $related = null, array $fields)
	{
		if (count($related))
		{
			foreach ($related as $name => $definition)
			{
				if (empty($definition))
				{
					unset($related[$name]);
					continue;
				}

				$type                  = sproutImport()->getImporterModel($definition);

				if (!$type)
				{
					return false;
				}

				$matchBy               = sproutImport()->getValueByKey('matchBy', $definition);
				$matchValue            = sproutImport()->getValueByKey('matchValue', $definition);
				$matchCriteria         = sproutImport()->getValueByKey('matchCriteria', $definition);
				$fieldType             = sproutImport()->getValueByKey('destinationField.type', $definition);
				$createIfNotFound      = sproutImport()->getValueByKey('createIfNotFound', $definition);
				$newElementDefinitions = sproutImport()->getValueByKey('newElementDefinitions', $definition);

				if (!$type && !$matchValue && !$matchBy)
				{
					continue;
				}

				if (!is_array($matchValue))
				{
					$matchValue = ArrayHelper::stringToArray($matchValue);
				}

				if (!count($matchValue))
				{
					continue;
				}

				$ids = array();

				foreach ($matchValue as $reference)
				{
					$criteria         = craft()->elements->getCriteria($type);
					$criteria->status = null;

					if (array_key_exists($matchBy, $criteria->getAttributes()))
					{
						$attributes = array('limit' => 1, $matchBy => $reference);
					}
					else
					{
						$attributes = array('limit' => 1, 'search' => $matchBy . ':' . $reference);
					}

					if (is_array($matchCriteria))
					{
						$attributes = array_merge($attributes, $matchCriteria);
					}

					try
					{
						$foundAll = true;
						$element  = $criteria->first($attributes);

						if (strtolower($type) == 'asset' && !$element)
						{
							sproutImport()->log('Missing > ' . $matchBy . ': ' . $reference);
						}

						if ($element)
						{
							$ids[] = $element->getAttribute('id');
						}
						else
						{
							$foundAll = false;
						}

						if (!$foundAll && $createIfNotFound && is_array($newElementDefinitions) && count($newElementDefinitions))
						{
							if (!empty($newElementDefinitions))
							{
								foreach ($newElementDefinitions as $definition)
								{
									$this->saveElement($definition);
								}
							}

							$elementIds = $this->getSavedResults(true);
							// Do we need to create the element?
							$ids = array_merge($ids, $elementIds);
						}
					}
					catch (\Exception $e)
					{
						sproutImport()->error($e->getMessage(), $e);

						continue;
					}
				}

				if (count($ids))
				{
					if (strtolower($fieldType) === 'matrix')
					{
						$blockType      = sproutImport()->getValueByKey('destinationField.blockType', $definition);
						$blockTypeField = sproutImport()->getValueByKey('destinationField.blockTypeField', $definition);

						if ($blockType && $blockTypeField)
						{
							$fields[$name] = array(
								'new1' => array(
									'type'    => $blockType,
									'enabled' => true,
									'fields'  => array(
										$blockTypeField => $ids
									)
								)
							);
						}
					}
					else
					{
						$fields[$name] = $ids;
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * @param null  $beforeSave
	 * @param array $data
	 *
	 * @return BaseElementModel|EntryModel|CategoryModel|UserModel
	 * @throws Exception
	 */
	protected function getElementModel($beforeSave = null)
	{
		$type  = $this->type;
		$name  = 'Craft\\' . $type . "Model";

		$model = new $name();

		if ($beforeSave)
		{
			$matchBy       = sproutImport()->getValueByKey('matchBy', $beforeSave);
			$matchValue    = sproutImport()->getValueByKey('matchValue', $beforeSave);
			$matchCriteria = sproutImport()->getValueByKey('matchCriteria', $beforeSave);

			if ($matchBy && $matchValue)
			{
				$criteria = craft()->elements->getCriteria($type);

				// The following is critical to import/relate elements not enabled
				$criteria->status = null;

				if (array_key_exists($matchBy, $criteria->getAttributes()))
				{
					$attributes = array('limit' => 1, $matchBy => $matchValue);
				}
				else
				{
					$attributes = array('limit' => 1, 'search' => $matchBy . ':' . $matchValue);
				}

				if (is_array($matchCriteria))
				{
					$attributes = array_merge($attributes, $matchCriteria);
				}

				try
				{
					$element = $criteria->first($attributes);

					if ($element)
					{
						return $element;
					}
				}
				catch (\Exception $e)
				{
					sproutImport()->error($e->getMessage());

					return false;
				}
			}
		}

		return $model;
	}

	/**
	 * @return array
	 */
	public function getChannelSections()
	{
		$selects  = array();
		$sections = craft()->sections->getAllSections();
		if (!empty($sections))
		{
			foreach ($sections as $section)
			{

				if ($section->type == 'single')
				{
					continue;
				}

				$selects[$section->handle] = $section->name;
			}
		}

		return $selects;
	}

	/**
	 * @param string                        $element
	 * @param BaseSproutImportFieldImporter $fieldClass
	 *
	 * @return array
	 */
	public function getFieldsByType($element = "Entry", BaseSproutImportFieldImporter $fieldClass)
	{
		$fields = craft()->fields->getFieldsByElementType($element);

		$type = $fieldClass->getName();

		$texts = array();
		
		if (!empty($fields))
		{
			foreach ($fields as $field)
			{
				if ($field->type == $type)
				{
					$texts[] = $field;
				}
			}
		}

		return $texts;
	}

	private function getElementKeys()
	{
		return array(
			'@model', 'attributes', 'content'
		);
	}
}
