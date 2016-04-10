<?php

namespace Craft;

class SproutImport_FakerService extends BaseApplicationComponent
{
	private $lib;

	/**
	 * SproutImport_FakerService constructor.
	 */
	public function __construct()
	{
		require_once dirname(__FILE__) . '/../vendor/autoload.php';

		$this->lib = \Faker\Factory::create();
	}

	/**
	 * @return \Faker\Generator
	 */
	public function getGenerator()
	{
		return $this->lib;
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