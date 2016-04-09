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

	protected $id = null;

	protected $valid;

	protected $fakerService;

	protected $settings;

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
			$model       = $this->getModel();
			$this->model = $model;

			$this->populateModel($model, $settings);
			$this->validate();
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
	public function getName()
	{
		return str_replace('SproutImportImporter', '', $this->getId());
	}

	/**
	 * @param string $pluginHandle
	 */
	final public function getId()
	{
		$importerClass = str_replace('Craft\\', '', get_class($this));

		$this->id = $importerClass;

		return $importerClass;
	}

	/**
	 * @todo - do we need this anymore?
	 *         We now have a BaseSproutImportElementImporter class
	 *
	 * @return bool
	 */
	public function isElement()
	{
		return false;
	}

	public function setSettings($settings)
	{
		$this->settings = $settings;
	}

	public function getErrors()
	{
		return $this->model->getErrors();
	}

	/**
	 * @param null $handle
	 *
	 * @return null
	 */
	public function getObjectByHandle($handle = null)
	{
		return null;
	}

	/**
	 * @return null
	 */
	public function getModel()
	{
		$handle = $this->settings['handle'];

		$exist = $this->getObjectByHandle($handle);

		if ($exist != null)
		{
			return $exist;
		}
		else
		{
			$model = $this->defineModel();

			return new $model;
		}
	}

	/**
	 * @return string
	 */
	public function populateModel($model, $settings)
	{
		$model->setAttributes($settings);
		$this->model = $model;
	}

	/**
	 * @return mixed
	 */
	public function isValid()
	{
		return $this->valid;
	}

	/**
	 * @return bool
	 */
	public function validate()
	{
		$this->valid = $this->model->validate();
	}

	/**
	 * @return string
	 */
	abstract public function save();

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	abstract public function deleteById($id);

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
	 * @return string
	 */
	public function getSettingsHtml()
	{
		return "";
	}
}
