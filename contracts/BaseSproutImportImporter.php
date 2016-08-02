<?php
namespace Craft;

/**
 * Class BaseSproutImportImporter
 *
 * @package Craft
 */
abstract class BaseSproutImportImporter
{
	public $model;

	protected $importerClass = null;

	protected $valid;

	protected $settings;

	protected $data;

	protected $fakerService;

	protected $errors = array();
	/**
	 * BaseSproutImportImporter constructor.
	 *
	 * @param array $settings
	 * @param null  $fakerService
	 */
	public function __construct($settings = array(), $fakerService = null)
	{
		$this->settings = $settings;

		if (count($settings))
		{
			$model = $this->getModel();

			$this->setModel($model, $settings);
		}

		if ($fakerService == null)
		{
			$this->fakerService = sproutImport()->faker->getGenerator();
		}
		else
		{
			$this->fakerService = $fakerService;
		}
	}

	/**
	 * @return mixed
	 */
	final public function getImporterClass()
	{
		$importerClass = str_replace('Craft\\', '', get_class($this));

		$this->importerClass = $importerClass;

		return $importerClass;
	}

	/**
	 * The user-friendly name for the imported data type
	 *
	 * @return mixed
	 */
	abstract public function getName();

	/**
	 * The primary model that the Importer supports
	 *
	 * i.e. EntryModel => Entry
	 * i.e. SproutForms_FormModel => SproutForms_Form
	 *
	 * @return mixed
	 */
	abstract public function getModelName();

	/**
	 * @return bool
	 */
	public function isElement()
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function isSettings()
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function isField()
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function hasSeedGenerator()
	{
		return false;
	}

	/**
	 * @param $model
	 */
	public function setModel($model, $settings = array())
	{
		if (count($settings))
		{
			$model->setAttributes($settings);
		}

		$this->model = $model;
	}

	/**
	 * @return mixed
	 */
	public function getModel()
	{
		if (!$this->model)
		{
			$className = $this->getModelName() . "Model";
			$model     = sproutImport()->getModelNameWithNamespace($className);

			if (!class_exists($model))
			{
				$this->addError($model . ' not found.', 'not-found');

				return $model;
			}

			$this->model = new $model;
		}

		return $this->model;
	}

	/**
	 * @return bool
	 */
	public function resolveRelatedSettings()
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function resolveNestedSettings()
	{
		return true;
	}

	/**
	 * @param $settings
	 */
	public function setSettings($settings)
	{
		$this->settings = $settings;
	}

	/**
	 * @return string
	 */
	public function getSettingsHtml()
	{
		return "";
	}

	/**
	 * @return string
	 */
	public function getSeedCount()
	{
		$name = $this->getModelName();

		$count = sproutImport()->seed->getSeedCountByElementType($name);

		return $count;
	}

	/**
	 * @param $data
	 */
	public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * @return array
	 */
	public function defineKeys()
	{
		return array();
	}

	/**
	 * @return mixed
	 */
	public function getModelErrors()
	{
		return $this->model->getErrors();
	}

	public function addError($message, $key = false)
	{
		if ($key)
		{
			$this->errors[$key] = $message;
		}
		else
		{
			$this->errors[] = $message;
		}
	}

	public function getError($key)
	{
		return (isset($this->errors[$key])) ? $this->errors[$key] : false;
	}

	public function getErrors()
	{
		return $this->errors;
	}

	public function hasErrors()
	{
		return (!empty($this->errors)) ? true : false;
	}

	public function clearErrors()
	{
		$this->errors = array();
	}
}
