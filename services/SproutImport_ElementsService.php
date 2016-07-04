<?php
namespace Craft;

class SproutImport_ElementsService extends BaseApplicationComponent
{
	/**
	 * @type ImporterModel type
	 */
	private $type;

	/**
	 * @var ElementType
	 */
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
		$modelName  = sproutImport()->getImporterModelName($element);
		$this->type = $modelName;

		// Adds extra element keys to pass validation
		$importerClass = sproutImport()->getImporterByModelName($modelName, $element);

		$importerElementKeys = $importerClass->defineKeys();

		$elementKeys = array_merge($this->getElementKeys(), $importerElementKeys);

		$inputKeys = array_keys($element);

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

		$beforeSave = sproutImport()->getValueByKey('content.beforeSave', $element);

		$model = $importerClass->getPopulatedModel();

		$content    = sproutImport()->getValueByKey('content', $element);
		$fields     = sproutImport()->getValueByKey('content.fields', $element);
		$related    = sproutImport()->getValueByKey('content.related', $element);
		$attributes = sproutImport()->getValueByKey('attributes', $element);

		if (!empty($fields) && method_exists($importerClass, 'getAllFieldHandles'))
		{
			// Catches invalid field handles stops the importing
			$elementFieldHandles = $importerClass->getAllFieldHandles();

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

		unset($element['content']['related']);

		$eventParams = array(
			'element' => $model,
			'seed'    => $seed,
			'@model'  => $modelName,
			'source'  => $source
		);

		$event = new Event($this, $eventParams);

		sproutImport()->onBeforeImportElement($event);

		$saved = false;

		if ($model->validate())
		{
			$isNewElement = !$model->id;

			try
			{
				$importerClass->setData($element);

				try
				{
					$saved = $importerClass->save();

					// Get updated model after save
					$model = $importerClass->getPopulatedModel();

					// Check for field setting errors
					if (!empty($model->getErrors()))
					{
						$this->logErrorByModel($model);

						return false;
					}
				}
				catch (\Exception $e)
				{
					$message = Craft::t("Error on importer save method. \n ");
					$message .= $e->getMessage();
					sproutImport()->addError($message, 'save-importer');

					return false;
				}

				if ($saved)
				{
					$importerClass->resolveNestedSettings($model, $element);
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
			$this->logErrorByModel($model);

			return false;
		}

		if ($saved)
		{
			// Pass the updated model after save
			$eventParams['element'] = $model;

			$event = new Event($this, $eventParams);

			sproutImport()->onAfterImportElement($event);

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
	public function resolveRelationships(array $related = null, array $fields)
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

				$type = sproutImport()->getImporterModelName($definition);

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
	protected function getElementModel($beforeSave = null, $populatedModel)
	{
		if ($this->getModelByMatches($beforeSave))
		{
			return $this->getModelByMatches($beforeSave);
		}

		return $populatedModel;
	}

	public function getModelByMatches($beforeSave)
	{
		$type = $this->type;

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
				}
			}
		}

		return false;
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

	/**
	 * @param $model
	 */
	public function logErrorByModel($model)
	{
		$errorLog               = array();
		$errorLog['errors']     = $model->getErrors();
		$errorLog['attributes'] = $model->getAttributes();

		// make error unique
		$errorKey = serialize($model->getAttributes());

		sproutImport()->addError($errorLog, $errorKey);
	}

	/**
	 * @return array
	 */
	private function getElementKeys()
	{
		return array(
			'@model', 'attributes', 'content'
		);
	}
}
