<?php
namespace Craft;

/**
 * Class SproutImportService
 *
 * @package Craft
 * --
 * @property SproutImport_ElementsService $elements
 * @property SproutImport_FakerService    $faker
 * @property SproutImport_MockDataService $mockData
 * @property SproutImport_SeedService     $seed
 * @property SproutImport_SettingsService $settings
 * @property SproutImport_TasksService    $tasks
 */
class SproutImportService extends BaseApplicationComponent
{
	public $elements;
	public $faker;
	public $mockData;
	public $seed;
	public $settings;
	public $tasks;

	/**
	 * @type array
	 */
	protected $importers = array();

	/**
	 * @type array
	 */
	protected $seedImporters = array();

	/**
	 * @type array
	 */
	protected $fieldImporters = array();

	/**
	 * @type array
	 */
	protected $error = array();

	/**
	 * @type
	 */
	protected $filename;

	/**
	 * @type
	 */
	protected $elementsService;

	/**
	 * @param null $elementsService
	 */
	public function init($elementsService = null)
	{
		parent::init();

		if ($elementsService != null)
		{
			$this->elementsService = $elementsService;
		}
		else
		{
			$this->elementsService = craft()->elements;
		}

		$this->elements = Craft::app()->getComponent('sproutImport_elements');
		$this->faker    = Craft::app()->getComponent('sproutImport_faker');
		$this->mockData = Craft::app()->getComponent('sproutImport_mockData');
		$this->seed     = Craft::app()->getComponent('sproutImport_seed');
		$this->settings = Craft::app()->getComponent('sproutImport_settings');
		$this->tasks    = Craft::app()->getComponent('sproutImport_tasks');
	}

	/**
	 * Get all built-in and third-party importers
	 *
	 * @return BaseSproutImportImporter[]
	 */
	public function getSproutImportImporters()
	{
		// Allow third party plugins to register custom elements importers
		$importersToLoad = craft()->plugins->call('registerSproutImportImporters');

		if ($importersToLoad)
		{
			foreach ($importersToLoad as $plugin => $importers)
			{
				foreach ($importers as $importer)
				{
					if ($importer && $importer instanceof BaseSproutImportImporter)
					{
						$this->importers[$importer->getId()] = $importer;

						if ($importer->hasSeedGenerator())
						{
							$this->seedImporters[$plugin][] = $importer;
						}
					}
				}
			}
		}

		return $this->importers;
	}

	/**
	 * Return a list of importers by name
	 *
	 * @return array
	 */
	public function getSproutImportImportersByName()
	{
		$names     = array();
		$importers = $this->getSproutImportImporters();

		if (!empty($importers))
		{
			foreach ($importers as $importer)
			{
				$names[] = $importer->getName();
			}
		}

		return $names;
	}

	/**
	 * Get all native and third-party that have seed generators
	 *
	 * @return array
	 */
	public function getSproutImportSeedImporters()
	{
		$this->getSproutImportImporters();

		return $this->seedImporters;
	}

	/**
	 * Get all built-in and registered FieldImporters
	 *
	 * @return BaseSproutImport[]
	 */
	public function getSproutImportFields()
	{
		$fieldsToLoad = craft()->plugins->call('registerSproutImportFields');

		if ($fieldsToLoad)
		{
			foreach ($fieldsToLoad as $plugin => $fieldClasses)
			{
				foreach ($fieldClasses as $fieldClass)
				{
					if ($fieldClass && $fieldClass instanceof BaseSproutImportFieldImporter)
					{
						$this->fieldImporters[$fieldClass->getId()] = $fieldClass;
					}
				}
			}
		}

		return $this->fieldImporters;
	}

	/**
	 * Save an imported Element or Setting
	 *
	 * @param array $elements
	 * @param bool  $returnSavedElementIds
	 *
	 * @return array
	 * @throws Exception
	 * @throws \CDbException
	 * @throws \Exception
	 */
	public function save(array $rows, $seed = false, $filename = '')
	{
		$this->filename = $filename;

		if (!empty($rows))
		{
			$results = array();

			foreach ($rows as $row)
			{
				$model = $this->getImporterModelName($row);

				if (!$model)
				{
					return false;
				}

				if ($this->isElementType($model))
				{
					$result = $this->elements->saveElement($row, $seed, 'import');
				}
				else
				{
					$result = $this->settings->saveSetting($row, $seed);
				}
			}
		}

		return true;
	}

	/**
	 * Check if name given is an element type
	 *
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

	/**
	 * Get the related SproutImportImporter for the given model
	 *
	 * @param $settings
	 *
	 * @return null
	 */
	public function getImporterModelName($settings, $names = null)
	{
		// Catches invalid model type key
		if (!isset($settings['@model']))
		{
			$msg = Craft::t("Model key is invalid use @model.");

			$errorLog               = array();
			$errorLog['message']    = $msg;
			$errorLog['attributes'] = $settings;

			$this->addError($errorLog, 'invalid-model-key');

			return false;
		}

		// Remove the word 'Model' from the end of our setting
		$importerModel = str_replace('Model', '', $settings['@model']);

		if ($names == null)
		{
			$names = sproutImport()->getSproutImportImportersByName();
		}

		if (!in_array($importerModel, $names))
		{
			$msg = $importerModel . Craft::t(" Model could not be found.");

			$this->addError($msg, 'invalid-model');

			return false;
		}
		
		return $importerModel;
	}

	/**
	 * @param string $key
	 * @param mixed  $data
	 * @param mixed  $default
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
	 * Add a record of the imported item to the seed database
	 *
	 * @param $event
	 */
	public function trackImport($event)
	{
		$element = $event->params['element'];
		$seed    = $event->params['seed'];
		$type    = $event->params['@model'];
		$source  = $event->params['source'];

		$id = $element->id;

		if ($seed && $source == "import")
		{
			sproutImport()->seed->trackSeed($id, $type);
		}
	}

	/**
	 * @param string|mixed $message
	 * @param array|mixed  $data
	 * @param string       $level
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
	 * @param null   $data
	 * @param string $level
	 */
	public function error($message, $data = null, $level = LogLevel::Error)
	{
		$this->log($message, $data, $level);
	}

	/**
	 * On Before Import Element Event
	 *
	 * @param Event|SproutImport_onBeforeImportElement $event
	 *
	 * @throws \CException
	 */
	public function onBeforeImportElement(Event $event)
	{
		$this->raiseEvent('onBeforeImportElement', $event);
	}

	/**
	 * On After Import Element Event
	 *
	 * @param Event|SproutImport_onAfterImportElement $event
	 *
	 * @throws \CException
	 */
	public function onAfterImportElement(Event $event)
	{
		$this->raiseEvent('onAfterImportElement', $event);
	}

	/**
	 * On After Import Setting Event
	 *
	 * @param Event $event
	 *
	 * @throws \CException
	 */
	public function onAfterImportSetting(Event $event)
	{
		$this->raiseEvent('onAfterImportSetting', $event);
	}

	/**
	 * Divide array by sections
	 *
	 * @param $array
	 * @param $step
	 *
	 * @return array
	 */
	function sectionArray($array, $step)
	{
		$sectioned = array();

		$k = 0;
		for ($i = 0; $i < count($array); $i++)
		{
			if (!($i % $step))
			{
				$k++;
			}

			$sectioned[$k][] = $array[$i];
		}

		return array_values($sectioned);
	}

	/**
	 * @author     Chris Smith <code+php@chris.cs278.org>
	 * @copyright  Copyright (c) 2009 Chris Smith (http://www.cs278.org/)
	 * @license    http://sam.zoy.org/wtfpl/ WTFPL
	 *
	 * @param    string $value  Value to test for serialized form
	 * @param    mixed  $result Result of unserialize() of the $value
	 *
	 * @return    boolean      True if $value is serialized data, otherwise false
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
		$length = strlen($value);
		$end    = '';
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

	/**
	 * Get an Importer from it's model name
	 *
	 * @param $name
	 * @param $row
	 *
	 * @return mixed
	 */
	public function getImporterByModelName($name, $row)
	{
		$importerClassName = 'Craft\\' . $name . 'SproutImportImporter';

		// If it doesn't exists then it's an Element Importer
		if (!class_exists($importerClassName))
		{
			$importerClassName = 'Craft\\' . $name . 'SproutImportElementImporter';

			$importer = new $importerClassName($row);
		}
		else
		{
			$importer = new $importerClassName($row);
		}

		return $importer;
	}

	/**
	 * @param      $name
	 * @param      $fields
	 * @param null $fieldService
	 *
	 * @return array
	 */
	public function getFieldIdsByHandle($name, $fields, $fieldService = null)
	{
		if ($fieldService == null)
		{
			$fieldService = craft()->fields;
		}

		$entryFields = array();

		if (!empty($fields))
		{
			foreach ($fields as $field)
			{
				if (!is_numeric($field))
				{
					$fieldId = $fieldService->getFieldByHandle($field)->id;
				}
				else
				{
					$fieldId = $field;
				}

				$nameKey = rawurlencode($name);

				$entryFields[$nameKey][] = $fieldId;
			}
		}

		return $entryFields;
	}

	/**
	 * Make sure the Sprout Import temp folder is created
	 *
	 * @return string
	 */
	public function createTempFolder()
	{
		$folderPath = craft()->path->getTempUploadsPath() . 'sproutimport/';

		IOHelper::clearFolder($folderPath);

		IOHelper::ensureFolderExists($folderPath);

		return $folderPath;
	}

	/**
	 * Logs an error in cases where it makes more sense than to throw an exception
	 *
	 * @param mixed $msg
	 * @param array $vars
	 */
	public function addError($msg, $key = '', array $vars = array())
	{
		if (is_string($msg))
		{
			$msg = Craft::t($msg, $vars);
		}
		else
		{
			$msg = print_r($msg, true);
		}

		if (!empty($key))
		{
			$this->error[$key] = $msg;
		}
		else
		{
			$this->error = $msg;
		}
	}

	/**
	 * @return mixed error
	 */
	public function getError($key = '')
	{
		if (!empty($key) && isset($this->error[$key]))
		{
			return $this->error[$key];
		}
		else
		{
			return $this->error;
		}
	}

	/**
	 * @return array
	 */
	public function getErrors()
	{
		return $this->error;
	}

	/**
	 * @param $result
	 */
	public function addResults($result)
	{
		$filename = $this->filename;

		sproutImport()->log('Task result for ' . $filename, $result);
	}

	/**
	 * @param $name
	 *
	 * @return string
	 */
	public function getModelNameWithNamespace($name)
	{
		$findNamespace = strpos($name, "Craft");

		if (!is_numeric($findNamespace))
		{
			$name = "Craft\\" . $name;
		}

		return $name;
	}


	/**
	 * @param $name
	 *
	 * @return mixed
	 */
	public function getFieldImporterClassByType($name)
	{
		$this->getSproutImportFields();

		$fieldClass = null;
		$namespace  = $name . "SproutImportFieldImporter";

		if (isset($this->fieldImporters[$namespace]))
		{
			$fieldClass = $this->fieldImporters[$namespace];
		}

		return $fieldClass;
	}
}
