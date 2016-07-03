<?php
namespace Craft;

class SproutImportVariable
{
	/**
	 * @return int
	 */
	public function hasSeeds()
	{
		$seeds = craft()->sproutImport_seed->getAllSeeds();

		return count($seeds);
	}

	/**
	 * Return an instance of the \Faker\Generator
	 *
	 * @return \Faker\Generator
	 */
	public function getFaker()
	{
		$generator = sproutImport()->faker->getGenerator();

		return $generator;
	}
}