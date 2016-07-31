<?php
namespace Craft;

abstract class BaseSproutImportFieldImporter
{
	protected $id;

	protected $fieldModel;

	protected $fakerService;

	/**
	 * BaseSproutImportFieldImporter constructor.
	 *
	 * @param null $fakerService
	 */
	public function __construct($fakerService = null)
	{
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
	public abstract function getFieldTypeModelName();

	/**
	 * @return mixed
	 */
	public function getField()
	{
		$fieldModel = "\\Craft\\" . $this->getFieldTypeModelName();

		return new $fieldModel;
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return str_replace('SproutImportFieldImporter', '', $this->getId());
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
	 * @param FieldModel $fieldModel
	 */
	public function setField(FieldModel $fieldModel)
	{
		$this->fieldModel = $fieldModel;
	}

	/**
	 * @return bool
	 */
	public function canMockData()
	{
		return false;
	}

	/**
	 * @return mixed
	 */
	public abstract function getMockData();
}
