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

	protected $data;

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
	 * @return mixed
	 */
	final public function getId()
	{
		$importerClass = str_replace('Craft\\', '', get_class($this));

		$this->id = $importerClass;

		return $importerClass;
	}

	/**
	 * @return bool
	 */
	public function isElement()
	{
		return false;
	}

	/**
	 * Get Element Importer label name
	 *
	 * @return mixed|string
	 */
	public function getElementName()
	{
		$name = $this->getName();

		$element = craft()->elements->getElementType($name);

		if (method_exists($element, 'getName'))
		{
			return $element->getName();
		}
		else
		{
			return $this->getName();
		}
	}

	/**
	 * @param $settings
	 */
	public function setSettings($settings)
	{
		$this->settings = $settings;
	}

	/**
	 * @return mixed
	 */
	public function getErrors()
	{
		return $this->model->getErrors();
	}

	/**
	 * A generic method that allows you to define how to retrieve the model of
	 * an importable data type using it's handle.
	 *
	 * In the case for importing fields, this is the getFieldByHandle method in the Fields Service.
	 * In the case for importing sections, this is the getSectionByHandle method in the Sections Service.
	 *
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
		$model = $this->defineModel();

		$model = sproutImport()->getModelNameWithNamespace($model);

		if (!isset($this->settings['handle']))
		{
			return new $model;
		}

		$handle = $this->settings['handle'];

		$object = $this->getObjectByHandle($handle);

		if ($object != null)
		{
			return $object;
		}
		else
		{
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
	 * @return null
	 */
	public function getPopulatedModel()
	{
		return $this->model;
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

	/**
	 * @return string
	 */
	public function getSeedCount()
	{
		$name = $this->getName();

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
}
