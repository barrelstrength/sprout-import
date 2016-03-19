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

	public function isElement()
	{
		return false;
	}

	public function __construct($settings = array())
	{
		require_once dirname(__FILE__) . '/../vendor/autoload.php';
		if (count($settings))
		{
			$model = $this->getModel();
			$this->model = $model;

			$this->populateModel($model, $settings);
			$this->validate();
		}

		$faker = \Faker\Factory::create();
		$faker->addProvider(new \Faker\Provider\Lorem($faker));

		$this->fakerService = $faker;
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

	/**
	 * @return string
	 */
	abstract public function getModel();

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
