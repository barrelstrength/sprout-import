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

	/**
	 * @return BaseSproutImportImporter[]
	 */
	public function getSproutImportImporters()
	{
		return sproutImport()->getSproutImportImporters();
	}

	/**
	 * @return BaseSproutImport[]
	 */
	public function getSproutImportFieldImporters()
	{
		return sproutImport()->getSproutImportFieldImporters();
	}

	/**
	 * @param string
	 *
	 * @return bool
	 */
	public function isPluginInstalled($plugin)
	{
		$plugins = craft()->plugins->getPlugins(false);

		if (array_key_exists($plugin, $plugins))
		{
			return true;
		}

		return false;
	}
}