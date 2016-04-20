<?php
namespace Craft;

class SproutImportVariable
{
	/**
	 * @param string $type
	 *
	 * @return mixed
	 */
	public function hasSeeds()
	{
		$seeds = craft()->sproutImport_seed->getAllSeeds();

		return count($seeds);
	}

	/**
	 * @return mixed
	 */
	public function fakeDataGenerator()
	{
		$generator = sproutImport()->faker->getGenerator();

		return $generator;
	}
}