<?php
namespace Craft;

class SproutMigrateVariable
{
	public function hasSeeds()
	{
		$seeds = craft()->sproutMigrate_seed->getAllSeeds();

		return count($seeds);
	}
}