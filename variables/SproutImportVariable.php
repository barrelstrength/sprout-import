<?php
namespace Craft;

class SproutImportVariable
{
	/**
	 * @param string $type
	 *
	 * @return mixed
	 */
	public function hasSeeds($type = 'import')
	{
		$seeds = craft()->sproutImport_seed->getAllSeeds($type);

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