<?php
namespace Craft;

abstract class BaseFieldSproutImport
{

	protected $fakerService;
	protected $id;

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
		return str_replace('FieldSproutImport', '', $this->getId());
	}

	public abstract function getMockData();
}
