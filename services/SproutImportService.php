<?php
namespace Craft;

/**
 * Class SproutImportService
 *
 * @package Craft
 * --
 * @property SproutImport_ElementImporterService  $elementImporter
 * @property SproutImport_FakerService            $faker
 * @property SproutImport_MockDataService         $mockData
 * @property SproutImport_SeedService             $seed
 * @property SproutImport_Settings                $settings
 * @property SproutImport_SettingsImporterService $settingsImporter
 * @property SproutImport_TasksService            $tasks
 */
class SproutImportService extends BaseApplicationComponent
{
	public $elementImporter;
	public $faker;
	public $mockData;
	public $seed;
	public $settings;
	public $settingsImporter;
	public $tasks;

	/**
	 * @var array
	 */
	protected $importers = array();

	/**
	 * @var array
	 */
	protected $seedImporters = array();

	/**
	 * @var array
	 */
	protected $fieldImporters = array();

	/**
	 * @var array
	 */
	protected $error = array();

	/**
	 * @var
	 */
	protected $filename;

	/**
	 * @var
	 *
	 * @todo - can probably remove $elementsService in favor of BaseSproutImportElementImporter::isElement
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

		$this->elementImporter  = Craft::app()->getComponent('sproutImport_elementImporter');
		$this->faker            = Craft::app()->getComponent('sproutImport_faker');
		$this->mockData         = Craft::app()->getComponent('sproutImport_mockData');
		$this->seed             = Craft::app()->getComponent('sproutImport_seed');
		$this->settings         = Craft::app()->getComponent('sproutImport_settings');
		$this->settingsImporter = Craft::app()->getComponent('sproutImport_settingsImporter');
		$this->tasks            = Craft::app()->getComponent('sproutImport_tasks');
	}

	/**
	 * Get all built-in and third-party importers
	 *
	 * @return array
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
					// Pluck any Field Importers for their own list
					if ($importer && $importer instanceof BaseSproutImportFieldImporter)
					{
						$this->fieldImporters[$importer->getImporterClass()] = $importer;
						continue;
					}

					if ($importer && $importer instanceof BaseSproutImportImporter)
					{
						$this->importers[$importer->getImporterClass()] = $importer;

						if ($importer->hasSeedGenerator())
						{
							$this->seedImporters[$plugin][] = $importer;
						}
					}
				}
			}
		}

		ksort($this->importers);
		ksort($this->fieldImporters);
		ksort($this->seedImporters);

		return $this->importers;
	}

	/**
	 * Get all native and third-party that have seed generators
	 *
	 * @return array
	 */
	public function getSproutImportSeedImporters()
	{
		if (count($this->seedImporters))
		{
			return $this->seedImporters;
		}

		$this->getSproutImportImporters();

		return $this->seedImporters;
	}

	/**
	 * Get all built-in and registered FieldImporters
	 *
	 * @return BaseSproutImport[]
	 */
	public function getSproutImportFieldImporters()
	{
		// Make sure all of our Field Type classes are loaded
		craft()->components->getComponentsByType(ComponentType::Field);

		if (count($this->fieldImporters))
		{
			return $this->fieldImporters;
		}

		$this->getSproutImportImporters();

		return $this->fieldImporters;
	}

	/**
	 * Save an imported Element or Setting
	 *
	 * @todo - it appears the $filename variable may not be in use?
	 *       Should be in use for error logs to communicate which filename had an issue.
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
		$result         = "";
		$this->filename = $filename;

		if (!empty($rows))
		{
			foreach ($rows as $row)
			{
				$model = $this->getImporterModelName($row);

				// Confirm model for this row of import data is supported
				if (!$model)
				{
					return false;
				}

				if ($this->isElementType($model))
				{
					$result = sproutImport()->elementImporter->saveElement($row, $seed, 'import');
				}
				else
				{
					$result = sproutImport()->settingsImporter->saveSetting($row, $seed);
				}
			}
		}

		return $result;
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
	 * Get the Importer Model based on the "@model" key in the import data row
	 * and return it if it exists. Models can be defined with or without the
	 * word 'Model' in their name.
	 *
	 * Examples:
	 * - UserModel or User
	 * - FieldModel or Field
	 *
	 * @param $settings
	 *
	 * @return null
	 */
	public function getImporterModelName($settings)
	{
		// Log error if no '@model' key identifier is found
		if (!isset($settings['@model']))
		{
			$message = Craft::t("Model key is invalid. Each type of data being imported should be identified with a '@model' as the key.");

			$errorLog               = array();
			$errorLog['message']    = $message;
			$errorLog['attributes'] = $settings;

			$this->addError($errorLog, 'invalid-model-key');

			return false;
		}

		// Remove the word 'Model' from the end of our setting if it exists
		$importerModel = str_replace('Model', '', $settings['@model']);

		$importers = sproutImport()->getSproutImportImporters();

		$elementImporterClassName  = $importerModel . 'SproutImportElementImporter';
		$settingsImporterClassName = $importerModel . 'SproutImportSettingsImporter';

		if (!isset($importers[$elementImporterClassName]) && !isset($importers[$settingsImporterClassName]))
		{
			$message = $importerModel . Craft::t(" Model could not be found.");

			SproutImportPlugin::log($message, LogLevel::Error);
			$this->addError($message, 'invalid-model');

			return false;
		}

		return $importerModel;
	}

	/**
	 * Get an Importer from it's model name
	 *
	 * @param $name
	 * @param $data
	 *
	 * @return mixed
	 */
	public function getImporterByModelName($name, $data)
	{
		$importerClassName = 'Craft\\' . $name . 'SproutImportSettingsImporter';

		// If it doesn't exists then it's an Element Importer
		if (!class_exists($importerClassName))
		{
			$importerClassName = 'Craft\\' . $name . 'SproutImportElementImporter';
		}

		return new $importerClassName($data);
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
		$this->getSproutImportFieldImporters();

		$fieldClass = null;
		$namespace  = $name . "SproutImportFieldImporter";

		if (isset($this->fieldImporters[$namespace]))
		{
			$fieldClass = $this->fieldImporters[$namespace];
		}

		return $fieldClass;
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
			SproutImportPlugin::log(Craft::t('getValueByKey() was passed in a non-array as data.'), LogLevel::Error);

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
	public function errorLog($message, $data = null, $level = LogLevel::Error)
	{
		$this->log($message, $data, $level);
	}

	/**
	 * Logs an error in cases where it makes more sense than to throw an exception
	 *
	 * @param mixed $message
	 * @param array $vars
	 */
	public function addError($message, $key = '', array $vars = array())
	{
		if (is_string($message))
		{
			$message = Craft::t($message, $vars);
		}
		else
		{
			$message = print_r($message, true);
		}

		if (!empty($key))
		{
			$this->error[$key] = $message;
		}
		else
		{
			$this->error = $message;
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
}
