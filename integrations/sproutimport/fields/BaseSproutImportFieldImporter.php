<?php
namespace Craft;

abstract class BaseSproutImportFieldImporter
{

	protected $fakerService;
	protected $id;
	protected $fieldModel;

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

	public function setField(FieldModel $fieldModel)
	{
		$this->fieldModel = $fieldModel;
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

	public function getName()
	{
		return str_replace('SproutImportFieldImporter', '', $this->getId());
	}

	public abstract function getMockData();
}
