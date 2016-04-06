<?php
namespace Craft;

/**
 * Class SproutImportBaseImporter
 *
 * @package Craft
 */
abstract class SproutImportBaseImporter
{
	protected $id;

	public $model;

	protected $valid;

	protected $fakerService;

	protected $settings;

	public function isElement()
	{
		return false;
	}

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

	public function setSettings($settings)
	{
		$this->settings = $settings;
	}

	public function getErrors()
	{
		return $this->model->getErrors();
	}

	/**
	 * @param string $pluginHandle
	 */
	final public function setId($plugin, $importer)
	{
		$importerClass = str_replace('Craft\\', '', get_class($this));

		$this->id = $importerClass;
	}

	final public function getId()
	{
		return $this->id;
	}

	public function getObjectByHandle($handle = null)
	{
		return null;
	}

	/**
	 * @return string
	 */
	public function getModel()
	{
		$handle = $this->settings['handle'];

		$exist = $this->getObjectByHandle($handle);

		if($exist != null)
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
	 * @return bool
	 */
	public function validate()
	{
		$this->valid = $this->model->validate();
	}

	public function isValid()
	{
		return $this->valid;
	}

	/**
	 * @return string
	 */
	abstract public function save();

	abstract public function deleteById($id);

	//final public function run($model, $settings)
	//{
	//	$this->populateModel($model);
	//	$this->validate($model);
	//	$this->save($model);
	//}

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

	public function getMockSettings()
	{
		return "";
	}

	public function getName()
	{
		return str_replace('SproutImportImporter', '', $this->getId());
	}
}
