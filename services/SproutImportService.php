<?php
namespace Craft;

class SproutImportService extends BaseApplicationComponent
{

	/**
	 * @var SproutImportBaseImporter[]
	 */
	protected $importers = array();

	/** Sub Services
	 * @var
	 */
	public $setting;
	public $element;
	public $seed;

	protected $elementsService;


	/**
	 * Gives third party plugins a chance to register custom elements to import into
	 */
	public function init($elementsService = null)
	{
		parent::init();

		if($elementsService != null)
		{
			$this->elementsService = $elementsService;
		}
		else
		{
			$this->elementsService = craft()->elements;
		}

		$this->seed    = Craft::app()->getComponent('sproutImport_seed');
		$this->element = Craft::app()->getComponent('sproutImport_element');
		$this->setting = Craft::app()->getComponent('sproutImport_setting');

	}

	/**
	 * Get all buil-in and called importers
	 *
	 * @return SproutImportBaseImporter[]
	 */
	public function getSproutImportImporters()
	{
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

		return $this->importers;
	}

	/**
	 * @param array $tasks
	 *
	 * @throws Exception
	 * @return TaskModel
	 */
	public function createImportTasks(array $tasks, $seed = false)
	{
		if (!count($tasks))
		{
			throw new Exception(Craft::t('Unable to create element import task. No tasks found.'));
		}

		return craft()->tasks->createTask('SproutImport', Craft::t("Importing elements"), array('files' => $tasks,
		                                                                                        'seed'  => $seed ));
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
	public function save(array $rows, $seed = false)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		if(!empty($rows))
		{

			$results = array();

			foreach ($rows as $row)
			{
				$model = $this->getImporterModel($row);

				try
				{
					if($this->isElementType($model))
					{
						$result = $this->element->saveElement($row, $seed);
					}
					else
					{
							$result = $this->setting->saveSetting($row, $seed);
					}
				}
				catch (\Exception $e)
				{
					// @todo clarify what happened more in errors
					sproutImport()->error($e->getMessage());
				}
			}
		}

		if ($transaction && $transaction->active)
		{
			$transaction->commit();
		}

		$elementResults = $this->element->getSavedResults();
		$settingResults = $this->setting->getSavedResults();

		return array_merge($elementResults, $settingResults);
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

	/**
	 * Check if name given is an element type
	 * @param $name
	 *
	 * @return bool
	 */
	public function isElementType($name)
	{
		$elements = $this->elementsService->getAllElementTypes();

		$elementHandles = array_keys($elements);

		if (in_array($name, $elementHandles))
		{
			return true;
		}

		return false;
	}



	public function getImporterModel($settings)
	{
		if (!$settings['@model'])
		{
			return null;
		}

		// Remove the word 'Model' from the end of our setting
		$importerModel = str_replace('Model', '', $settings['@model']);

		return $importerModel;
	}

	/**
	 * @param $type
	 *
	 * @return mixed
	 */
	public function getModelClass($type)
	{
		$type = strtolower($type);

		return $this->getValueByKey("{$type}.model", $this->mapping);
	}

	/**
	 * @param $type
	 *
	 * @return mixed
	 */
	public function getServiceName($type)
	{
		$type = strtolower($type);

		return $this->getValueByKey("{$type}.service", $this->mapping);
	}

	/**
	 * @param $type
	 *
	 * @return mixed
	 */
	public function getMethodName($type)
	{
		$type = strtolower($type);

		return $this->getValueByKey("{$type}.method", $this->mapping);
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

	public function getImporter($row)
	{
		$importerModel = sproutImport()->getImporterModel($row);

		$importerClassName = $this->getImporterByName($importerModel);

		$importerClass = new $importerClassName($row);

		return $importerClass;
	}

	public function getImporterByName($name)
	{
		$importerClassName = 'Craft\\' . $name . 'SproutImportImporter';

		return $importerClassName;
	}

	public function getLatestSingleSection()
	{
		$result = array();

		$sections = craft()->sections->getSectionsByType(SectionType::Single);

		if (!empty($sections))
		{
			$singles = array();
			foreach ($sections as $section)
			{
				$id = $section->id;

				$singles[$id] = $section;
			}

			ksort($singles);

			$result = end($singles);
		}

		return $result;
	}

	/** Ensures that sprout import temp folder is created
	 *
	 * @return string
	 */
	public function createTempFolder()
	{

		$folderPath = craft()->path->getTempUploadsPath().'sproutimport/';

		IOHelper::clearFolder($folderPath);

		IOHelper::ensureFolderExists($folderPath);

		return $folderPath;
	}
}
