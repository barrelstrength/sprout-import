<?php
namespace Craft;

class SproutImportVariable
{
	public function hasSeeds($type = 'import')
	{
		$seeds = craft()->sproutImport_seed->getAllSeeds($type);

		return count($seeds);
	}
}