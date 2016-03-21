<?php

namespace Craft;


class SproutImport_ElementService extends BaseApplicationComponent
{

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
	public function saveElement(array $element, $seed = false)
	{

		$model = $this->getElementModel(sproutImport()->getValueByKey('content.beforeSave', $element), $element);

		$type       = sproutImport()->getValueByKey('@model', $element);
		$content    = sproutImport()->getValueByKey('content', $element);
		$fields     = sproutImport()->getValueByKey('content.fields', $element);
		$related    = sproutImport()->getValueByKey('content.related', $element);
		$attributes = sproutImport()->getValueByKey('attributes', $element);

		$this->resolveMatrixRelationships($fields);

		// @todo - when trying to import Sprout Forms Form Models,
		// which do not have any fields or content, running this method kills the script
		// moving the $related check to before the method runs, works.
		if (count($related))
		{
			$this->resolveRelationships($related, $fields);
		}

		// Allows author email to add as author of the entry
		if (isset($attributes['authorId']))
		{
			if (is_array($attributes['authorId']) && !empty($attributes['authorId']['email']))
			{
				$userEmail = $attributes['authorId']['email'];
				$userModel = craft()->users->getUserByUsernameOrEmail($userEmail);
				$authorId = $userModel->getAttribute('id');
				$attributes['authorId'] = $authorId;
			}
		}

		$model->setAttributes($attributes);
		unset($element['content']['related']);

		$model->setContent($content);
		$model->setContentFromPost($fields);

		$event = new Event($this, array('element' => $model));

		sproutImport()->onBeforeMigrateElement($event);

		sproutImport()->log("Begin validation of Element Model.");

		if ($model->validate())
		{
			$isNewElement = !$model->id;

			try
			{
				$importer = sproutImport()->getImporter($element);

				$importer->setModel($model);

				$saved = $importer->save();

				if ($saved && $isNewElement)
				{
					$this->savedElementIds[] = $model->id;
					$this->savedElements[] = $model->getTitle();

					$event = new Event($this, array('element' => $model, 'seed' => $seed, '@model' => $type));

					sproutImport()->onAfterMigrateElement($event);
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
				$title = sproutImport()->getValueByKey('content.title', $element);
				$msg = $title . ' ' . implode(', ', array_keys($fields)) . ' Check field values if it exists.';
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

			sproutImport()->error('Unable to validate.', $model->getErrors());
		}
	}

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
	public function resolveMatrixRelationships(&$fields)
	{
		if (empty($fields))
		{
			return;
		}

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

						$this->resolveRelationships($relatedFields, $blockFields);

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
	}

	/**
	 * @param array $related
	 * @param array $fields
	 *
	 * @throws Exception
	 */
	protected function resolveRelationships(array $related = null, array &$fields)
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

				$type                  = sproutImport()->getValueByKey('@model', $definition);
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
							if(!empty($newElementDefinitions))
							{
								foreach($newElementDefinitions as $definition)
								{
									$this->saveElement($definition);
								}
							}

							$elementIds = $this->getSavedResults(true);
							// Do we need to create the element?
							$ids = array_merge($ids, $elementIds);
						}
					} catch (\Exception $e)
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
	}


	/**
	 * @param null $beforeSave
	 * @param array $data
	 *
	 * @return BaseElementModel|EntryModel|CategoryModel|UserModel
	 * @throws Exception
	 */
	protected function getElementModel($beforeSave = null, array $data = array())
	{
		$type  = sproutImport()->getValueByKey('@model', $data);
		$name  = sproutImport()->getModelClass($type);

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
				} catch (\Exception $e)
				{
					sproutImport()->error($e->getMessage());
				}
			}
		}

		return $model;
	}

	public function getChannelSections()
	{
		$selects = array();
		$sections = craft()->sections->getAllSections();
		if (!empty($sections))
		{
			foreach ($sections as $section)
			{

				if ($section->type == 'single') continue;

				$selects[$section->handle] = $section->name;
			}
		}

		return $selects;
	}

	public function getFieldsByType($type = "RichText")
	{
		$fields = craft()->fields->getAllFields();

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
}