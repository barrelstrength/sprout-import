<?php
namespace Craft;

class AssetSproutImportElementImporter extends BaseSproutImportElementImporter
{
	/**
	 * @return mixed
	 */
	public function getModelName()
	{
		return 'Asset';
	}

	/**
	 * @return bool
	 * @throws Exception
	 * @throws \Exception
	 */
	public function save()
	{
		return craft()->assets->storeFile($this->model);
	}
}