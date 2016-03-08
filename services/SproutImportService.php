<?php
namespace Craft;

class SproutImportService extends BaseApplicationComponent
{
	/**
	 * Maps the element we support importing into
	 *
	 * @note Custom elements can be registered by defining registerSproutImportElements
	 * () in your plugin
	 * @var array
	 */
	protected $mapping = array();

	/**
	 * @var SproutImportBaseImporter[]
	 */
	protected $importers;

	/**
	 * Elements saved during the length of the import job
	 *
	 * @var array
	 */
	protected $savedElements = array();

	/**
	 * @var array
	 */
	protected $unsavedElements = array();

	/**
	 * @var array
	 */
	protected $updatedElements = array();

	public $seed;

	/**
	 * Gives third party plugins a chance to register custom elements to import into
	 */
	public function init()
	{
		parent::init();

		$this->seed = Craft::app()->getComponent('sproutImport_seed');

		$results = craft()->plugins->call('registerSproutImportElements');

		if ($results)
		{
			foreach ($results as $elements)
			{
				if (is_array($elements) && count($elements))
				{
					foreach ($elements as $type => $element)
					{
						// @todo - Add validation and enforce rules
						if (isset($element['model']) && isset($element['method']) && isset($element['service']))
						{
							// @todo - Think about adding checks to replace isset() checks
							$this->mapping[$type] = $element;
						}
					}
				}
			}
		}

		$importersToLoad = craft()->plugins->call('registerSproutImportImporters');

		if ($importersToLoad)
		{
			foreach ($importersToLoad as $plugin => $importers)
			{

				foreach ($importers as $importer)
				{
					if ($importer && $importer instanceof SproutImportBaseImporter)
					{
						$importer->setId($plugin, $importer);

						$this->importers[$importer->getId()] = $importer;
					}
				}
			}
		}
	}

	/**
	 * @param array $tasks
	 *
	 * @throws Exception
	 * @return TaskModel
	 */
	public function createImportElementsTasks(array $tasks, $seed = false)
	{
		if (!count($tasks))
		{
			throw new Exception(Craft::t('Unable to create element import task. No tasks found.'));
		}

		return craft()->tasks->createTask('SproutImport', Craft::t("Importing elements"), array('files' => $tasks,
		                                                                                        'seed'  => $seed ));
	}

	/**
	 * @param array $tasks
	 *
	 * @throws Exception
	 * @return TaskModel
	 */
	public function createImportSettingsTasks(array $tasks, $seed = false)
	{
		if (!count($tasks))
		{
			throw new Exception(Craft::t('Unable to create settings import task. No tasks found.'));
		}

		return craft()->tasks->createTask('SproutImport_Settings', Craft::t("Importing settings"), array(
			'files' => $tasks,
			'seed'  => $seed
		));
	}

	public function enqueueTasksByPost(array $tasks)
	{
		if (!count($tasks))
		{
			throw new Exception(Craft::t('No tasks to enqueue'));
		}

		$taskName    = 'Craft Migration';
		$description = 'Sprout Migrate By Post Request';

		return craft()->tasks->createTask('SproutImport_Post', Craft::t($description), array('elements' => $tasks));
	}

	/**
	 * @param array $elements
	 * @param bool $returnSavedElementIds
	 *
	 * @return array
	 * @throws Exception
	 * @throws \CDbException
	 * @throws \Exception
	 */
	public function save(array $elements, $returnSavedElementIds = false, $seed = false)
	{
		$transaction     = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		$savedElementIds = array();

		foreach ($elements as $element)
		{
			$type       = $this->getValueByKey('type', $element);
			$model      = $this->getElementModel($this->getValueByKey('content.beforeSave', $element), $element);
			$content    = $this->getValueByKey('content', $element);
			$fields     = $this->getValueByKey('content.fields', $element);
			$related    = $this->getValueByKey('content.related', $element);
			$attributes = $this->getValueByKey('attributes', $element);

			$this->resolveMatrixRelationships($fields);

			// @todo - when trying to import Sprout Forms Form Models,
			// which do not have any fields or content, running this method kills the script
			// moving the $related check to before the method runs, works.
			if (count($related))
			{
				$this->resolveRelationships($related, $fields);
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
					$saved = craft()->{$this->getServiceName($type)}->{$this->getMethodName($type)}($model, $element);

					if ($saved && $isNewElement)
					{
						$savedElementIds[]     = $model->id;
						$this->savedElements[] = $model->getTitle();


						$event = new Event($this, array( 'element' => $model, 'seed' => $seed, 'type' => $type ));

						$this->onAfterMigrateElement($event);

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

				} catch (\Exception $e)
				{
					$this->unsavedElements[] = array('title' => $model->getTitle(), 'error' => $e->getMessage());
					$title                   = $this->getValueByKey('content.title', $element);
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

				sproutImport()->error('Unable to validate.', $model->getErrors());
			}
		}

		if ($transaction && $transaction->active)
		{
			$transaction->commit();
		}

		$result = array(
			'saved'          => count($this->savedElements),
			'updated'        => count($this->updatedElements),
			'unsaved'        => count($this->unsavedElements),
			'unsavedDetails' => $this->unsavedElements,
		);

		return $returnSavedElementIds ? $savedElementIds : $result;
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

	// Process our settings
	// This is primarily called when we have a group of settings we want to loop through and save
	public function saveSettings($settings = array(), $seed = false)
	{
		if (empty($settings))
		{
			return false;
		}

		if ($seed)
		{
			craft()->sproutImport_seed->seed = true;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		foreach ($settings as $importSettings)
		{
			try
			{
				$this->saveSetting($importSettings);
			} catch (\Exception $e)
			{
				// @todo clarify what happened more in errors
				sproutImport()->error($e->getMessage());
			}
		}

		if ($transaction && $transaction->active)
		{
			$transaction->commit();
		}
	}

	// Returns $model if saved, or false if failed
	// This can be called in a loop, or called directly if we know we just have one setting and want and ID back.
	public function saveSetting($settings, $seed = false)
	{
		if ($seed)
		{
			craft()->sproutImport_seed->seed = true;
		}

		$importer = $this->getImporter($settings);

		if ($importer->isValid() && $importer->save())
		{
			if (craft()->sproutImport_seed->seed)
			{
				craft()->sproutImport_seed->trackSeed($importer->model->id, $this->getImporterModel($settings));
			}

			// @todo - probably want to protect $importer->model and update to $importer->getModel()
			$importer->resolveNestedSettings($importer->model, $settings);

			// @todo - keep track of what we've saved for reporting later.
			sproutImport()->log('Saved ID: ' . $importer->model->id);

			return $importer->model;
		}
		else
		{
			sproutImport()->error('Unable to validate.');
			sproutImport()->error($importer->getErrors());

			return false;
		}
	}

	public function getJson($file)
	{
		$content = file_get_contents($file);

		if ($content && ($content = json_decode($content, true)) && !json_last_error())
		{
			return $content;
		}

		return false;
	}

	public function getImporter($settings)
	{
		$importerModel = $this->getImporterModel($settings);

		$elements = craft()->elements->getAllElementTypes();

		$elementHandles = array_keys($elements);

		if (in_array($importerModel, $elementHandles))
		{
			$importerClassName = 'Craft\\ElementSproutImportImporter';

			$importerClass = new $importerClassName($settings, $importerModel);

		}
		else
		{
			$importerClassName = 'Craft\\' . $importerModel . 'SproutImportImporter';

			$importerClass = new $importerClassName($settings);
		}

		return $importerClass;
	}

	public function getImporterModel($settings)
	{
		if (!$settings['@model'])
		{
			return null;
		}

		// Remove the word 'Model' from the end of our setting
		$importerModel = substr($settings['@model'], 0, -5);

		return $importerModel;
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
		$type  = $this->getValueByKey('type', $data);
		$name  = $this->getModelClass($type);
		$model = new $name();

		if ($beforeSave)
		{
			$matchBy       = $this->getValueByKey('matchBy', $beforeSave);
			$matchValue    = $this->getValueByKey('matchValue', $beforeSave);
			$matchCriteria = $this->getValueByKey('matchCriteria', $beforeSave);

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

	/**
	 * @param $type
	 *
	 * @return mixed
	 */
	protected function getModelClass($type)
	{
		$type = strtolower($type);

		return $this->getValueByKey("{$type}.model", $this->mapping);
	}

	/**
	 * @param $type
	 *
	 * @return mixed
	 */
	protected function getServiceName($type)
	{
		$type = strtolower($type);

		return $this->getValueByKey("{$type}.service", $this->mapping);
	}

	/**
	 * @param $type
	 *
	 * @return mixed
	 */
	protected function getMethodName($type)
	{
		$type = strtolower($type);

		return $this->getValueByKey("{$type}.method", $this->mapping);
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

				$type                  = $this->getValueByKey('type', $definition);
				$matchBy               = $this->getValueByKey('matchBy', $definition);
				$matchValue            = $this->getValueByKey('matchValue', $definition);
				$matchCriteria         = $this->getValueByKey('matchCriteria', $definition);
				$fieldType             = $this->getValueByKey('destinationField.type', $definition);
				$createIfNotFound      = $this->getValueByKey('createIfNotFound', $definition);
				$newElementDefinitions = $this->getValueByKey('newElementDefinitions', $definition);

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
							// Do we need to create the element?
							$ids = array_merge($ids, $this->save($newElementDefinitions, true));
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
						$blockType      = $this->getValueByKey('destinationField.blockType', $definition);
						$blockTypeField = $this->getValueByKey('destinationField.blockTypeField', $definition);

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
	 * @param string $key
	 * @param mixed $data
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function getValueByKey($key, $data, $default = null)
	{
		if (!is_array($data))
		{
			sproutImport()->error('getValueByKey() was passed in a non-array as data.');

			return $default;
		}

		if (!is_string($key) || empty($key) || !count($data))
		{
			return $default;
		}

		// @assert $key contains a dot notated string
		if (strpos($key, '.') !== false)
		{
			$keys = explode('.', $key);

			foreach ($keys as $innerKey)
			{
				if (!array_key_exists($innerKey, $data))
				{
					return $default;
				}

				$data = $data[$innerKey];
			}

			return $data;
		}

		return array_key_exists($key, $data) ? $data[$key] : $default;
	}

	/**
	 * @param string|mixed $message
	 * @param array|mixed $data
	 * @param string $level
	 */
	public function log($message, $data = null, $level = LogLevel::Info)
	{
		if ($data)
		{
			$data = print_r($data, true);
		}

		if (!is_string($message))
		{
			$message = print_r($message, true);
		}

		SproutImportPlugin::log(PHP_EOL . $message . PHP_EOL . PHP_EOL . $data, $level);
	}

	/**
	 * @param        $message
	 * @param null $data
	 * @param string $level
	 */
	public function error($message, $data = null, $level = LogLevel::Error)
	{
		$this->log($message, $data, $level);
	}

	/**
	 * @param Event|SproutImport_onBeforeMigrateElement $event
	 *
	 * @throws \CException
	 */
	public function onBeforeMigrateElement(Event $event)
	{
		$this->raiseEvent('onBeforeMigrateElement', $event);
	}

	/**
	 * @param Event|SproutImport_onAfterMigrateElement $event
	 *
	 * @throws \CException
	 */
	public function onAfterMigrateElement(Event $event)
	{
		$this->raiseEvent('onAfterMigrateElement', $event);
	}

	/**
	 * Divide array by sections
	 * @param $array
	 * @param $step
	 * @return array
	 */
	function sectionArray($array, $step)
	{
		$sectioned = array();

		$k = 0;
		for ( $i=0;$i < count($array); $i++ ) {
			if ( !($i % $step) ) {
				$k++;
			}

			$sectioned[$k][] = $array[$i];
		}
		return array_values($sectioned);
	}

	/**
	* @author		Chris Smith <code+php@chris.cs278.org>
	* @copyright	Copyright (c) 2009 Chris Smith (http://www.cs278.org/)
	* @license		http://sam.zoy.org/wtfpl/ WTFPL
	* @param		string	$value	Value to test for serialized form
	* @param		mixed	$result	Result of unserialize() of the $value
	* @return		boolean			True if $value is serialized data, otherwise false
	*/
	function isSerialized($value, &$result = null)
	{
		// Bit of a give away this one
		if (!is_string($value))
		{
			return false;
		}
		// Serialized false, return true. unserialize() returns false on an
		// invalid string or it could return false if the string is serialized
		// false, eliminate that possibility.
		if ($value === 'b:0;')
		{
			$result = false;
			return true;
		}
		$length	= strlen($value);
		$end	= '';
		switch ($value[0])
		{
			case 's':
				if ($value[$length - 2] !== '"')
				{
					return false;
				}
			case 'b':
			case 'i':
			case 'd':
				// This looks odd but it is quicker than isset()ing
				$end .= ';';
			case 'a':
			case 'O':
				$end .= '}';
				if ($value[1] !== ':')
				{
					return false;
				}
				switch ($value[2])
				{
					case 0:
					case 1:
					case 2:
					case 3:
					case 4:
					case 5:
					case 6:
					case 7:
					case 8:
					case 9:
						break;
					default:
						return false;
				}
			case 'N':
				$end .= ';';
				if ($value[$length - 1] !== $end[0])
				{
					return false;
				}
				break;
			default:
				return false;
		}
		if (($result = @unserialize($value)) === false)
		{
			$result = null;
			return false;
		}
		return true;
	}

	/** Migrate elements to Craft
	 * @param $elements
	 * @throws Exception
	 */
	public function setEnqueueTasksByPost($elements)
	{

		// support serialize format
		if(sproutImport()->isSerialized($elements))
		{
			$elements = unserialize($elements);

		}

		// Divide array for the tasks service
		$tasks = sproutImport()->sectionArray($elements, 10);

		sproutImport()->enqueueTasksByPost($tasks);

		try
		{
			craft()->userSession->setNotice(Craft::t('({tasks}) Tasks queued successfully.', array('tasks' => count($tasks))));
		}
		catch(\Exception $e)
		{
			craft()->userSession->setError($e->getMessage());
		}

	}
}
