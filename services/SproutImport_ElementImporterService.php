<?php
namespace Craft;

/**
 * Class SproutImport_ElementsService
 *
 * @package Craft
 */
class SproutImport_ElementImporterService extends BaseApplicationComponent
{
	/**
	 * @type ImporterModel type
	 */
	private $modelName;

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
	 * @param array $data
	 * @param bool  $seed
	 * @param null  $source
	 *
	 * @return array
	 * @internal param array $elements
	 * @internal param bool $returnSavedElementIds
	 *
	 */
	public function saveElement(array $data, $seed = false)
	{
		$modelName       = sproutImport()->getImporterModelName($data);
		$this->modelName = $modelName;

		/**
		 * Adds extra element keys to pass validation
		 *
		 * @var BaseSproutImportElementImporter $importerClass
		 */
		$importerClass = sproutImport()->getImporterByModelName($modelName, $data);

		$additionalDataKeys = $importerClass->getImporterDataKeys();

		$definedDataKeys = array_merge($this->getElementDataKeys(), $additionalDataKeys);

		$dataKeys = array_keys($data);

		// Catches invalid element keys
		$dataKeysDiff = array_diff($dataKeys, $definedDataKeys);

		if (!empty($dataKeysDiff))
		{
			$inputKeysText = implode(', ', $dataKeysDiff);

			$message = Craft::t("Invalid element keys $inputKeysText.");

			SproutImportPlugin::log($message, LogLevel::Error);

			sproutImport()->addError($message, $inputKeysText);

			return false;
		}

		$model = $importerClass->getModel();

		$fields = sproutImport()->getValueByKey('content.fields', $data);

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

					$message = Craft::t("Could not find the $fieldHandle field.");

					SproutImportPlugin::log($message, LogLevel::Error);
					sproutImport()->addError($message, $key);

					return false;
				}
			}
		}

		unset($data['content']['related']);

		$event = new Event($this, array(
			'@model'  => $modelName,
			'element' => $model,
			'seed'    => $seed
		));

		sproutImport()->onBeforeImportElement($event);

		$saved = false;

		if ($model->validate())
		{
			$isNewElement = !$model->id;

			try
			{
				try
				{
					$importerClass->save();

					// Get updated model after save
					$model = $importerClass->getModel();

					$errors = $model->getErrors();

					// Check for field setting errors
					if (!empty($errors))
					{
						$this->logErrorByModel($model);

						return false;
					}

					$saved = true;
				}
				catch (\Exception $e)
				{
					$message = Craft::t("Error on importer save method. \n ");
					$message .= $e->getMessage();

					SproutImportPlugin::log($message, LogLevel::Error);
					sproutImport()->addError($message, 'save-importer');

					return false;
				}

				if ($saved)
				{
					$importerClass->resolveNestedSettings($model, $data);
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
				$this->unsavedElements[] = array(
					'title' => $model->getTitle(),
					'error' => $e->getMessage()
				);

				$title = sproutImport()->getValueByKey('content.title', $data);

				$fieldsMessage = (is_array($fields)) ? implode(', ', array_keys($fields)) : $fields;

				$message = $title . ' ' . $fieldsMessage . ' Check field values if it exists.';

				SproutImportPlugin::log($message, LogLevel::Error);

				sproutImport()->addError($message, 'save-element-error');

				sproutImport()->addError($e->getMessage(), 'save-element-error-message');
			}
		}
		else
		{
			$this->logErrorByModel($model);

			return false;
		}

		if ($saved)
		{
			$event = new Event($this, array(
				'@model'  => $modelName,
				'element' => $model,
				'seed'    => $seed
			));

			sproutImport()->onAfterImportElement($event);

			if ($seed)
			{
				sproutImport()->seed->trackSeed($model->id, $modelName);
			}

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
							$fields[$field][$block]['fields'] = $blockFields;
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

				$matchBy          = sproutImport()->getValueByKey('matchBy', $definition);
				$matchValue       = sproutImport()->getValueByKey('matchValue', $definition);
				$matchCriteria    = sproutImport()->getValueByKey('matchCriteria', $definition);
				$createIfNotFound = sproutImport()->getValueByKey('createIfNotFound', $definition);
				$newElements      = sproutImport()->getValueByKey('newElements', $definition);

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
							SproutImportPlugin::log('Missing > ' . $matchBy . ': ' . $reference);
						}

						if ($element)
						{
							$ids[] = $element->getAttribute('id');
						}
						else
						{
							$foundAll = false;
						}

						if (!$foundAll && $createIfNotFound && is_array($newElements) && count($newElements))
						{
							if (!empty($newElements))
							{
								foreach ($newElements as $definition)
								{
									$this->saveElement($definition);
								}
							}

							$elementIds = $this->getSavedResults(true);

							$ids = array_merge($ids, $elementIds);
						}
					}
					catch (\Exception $e)
					{
						$message['errorMessage'] = $e->getMessage();
						$message['errorObject']  = $e;

						SproutImportPlugin::log($message, LogLevel::Error);

						continue;
					}
				}

				if (count($ids))
				{
					$fields[$name] = $ids;
				}
				else
				{
					$fields[$name] = array(0);
				}
			}
		}

		return $fields;
	}

	public function getModelByMatches($updateElement)
	{
		$modelName = $this->modelName;

		if ($updateElement)
		{
			$matchBy       = sproutImport()->getValueByKey('matchBy', $updateElement);
			$matchValue    = sproutImport()->getValueByKey('matchValue', $updateElement);
			$matchCriteria = sproutImport()->getValueByKey('matchCriteria', $updateElement);

			if ($matchBy && $matchValue)
			{
				if (is_array($matchValue))
				{
					$matchValue = $matchValue[0];

					if (count($matchValue) > 0)
					{
						$message = Craft::t('The updateElement key can only retrieve a single match. Array with multiple values was provided. Only the first value has been used to find a match: {matchValue}', array(
							'matchValue' => $matchValue
						));

						SproutImportPlugin::log($message, LogLevel::Warning);
					}
				}

				$criteria = craft()->elements->getCriteria($modelName);

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

					// Bug: if searchScore is not integer it does not validate
					if (isset($element->searchScore))
					{
						$element->searchScore = round($element->searchScore);
					}

					if ($element)
					{
						return $element;
					}
				}
				catch (\Exception $e)
				{
					SproutImportPlugin::log($e->getMessage(), LogLevel::Error);
					sproutImport()->addError($e->getMessage(), 'invalid-model-match');

					return false;
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
	 * @param $model
	 */
	public function logErrorByModel($model)
	{
		$errorLog               = array();
		$errorLog['errors']     = $model->getErrors();
		$errorLog['attributes'] = $model->getAttributes();

		// make error unique
		$errorKey = serialize($model->getAttributes());

		SproutImportPlugin::log(Craft::t('Errors via logErrorByModel'), LogLevel::Error);

		sproutImport()->addError($errorLog, $errorKey);
	}

	/**
	 * @return array
	 */
	private function getElementDataKeys()
	{
		return array(
			'@model', 'attributes', 'content', 'settings'
		);
	}
}
