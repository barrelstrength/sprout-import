<?php
namespace Craft;

class SproutImportVariable
{
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
	 * Confirm if a specific plugin is installed
	 *
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

	/**
	 * Get all Element and Settings importers
	 *
	 * @return BaseSproutImportImporter[]
	 */
	public function getSproutImportImporters()
	{
		return sproutImport()->getSproutImportImporters();
	}

	/**
	 * Get all Field importers
	 *
	 * @return BaseSproutImport[]
	 */
	public function getSproutImportFieldImporters()
	{
		return sproutImport()->getSproutImportFieldImporters();
	}

	/**
	 * Confirm if any seeds exist
	 *
	 * @return int
	 */
	public function hasSeeds()
	{
		$seeds = sproutImport()->seed->getAllSeeds();

		return count($seeds);
	}
}