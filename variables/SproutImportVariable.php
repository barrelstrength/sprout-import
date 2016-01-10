<?php
namespace Craft;

class SproutImportVariable
{
	public function hasSeeds()
	{
		$seeds = craft()->sproutImport_seed->getAllSeeds();

		return count($seeds);
	}
}