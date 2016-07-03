<?php

namespace Craft;

class SproutImport_FakerService extends BaseApplicationComponent
{
	/**
	 * @type \Faker\Generator
	 */
	private $fakerGenerator;

	/**
	 * SproutImport_FakerService constructor.
	 */
	public function __construct()
	{
		require_once dirname(__FILE__) . '/../vendor/autoload.php';

		$this->fakerGenerator = \Faker\Factory::create();
	}

	/**
	 * @return \Faker\Generator
	 */
	public function getGenerator()
	{
		return $this->fakerGenerator;
	}

	/**
	 * @param $name
	 *
	 * @return mixed
	 */
	public function generateFakeField($name)
	{
		$namespace  = 'Craft\\' . $name . "SproutImportFieldImporter";

		$fieldClass = new $namespace();

		return $fieldClass->getMockData();
	}
}