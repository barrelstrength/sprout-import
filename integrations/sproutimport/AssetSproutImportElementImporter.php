<?php
namespace Craft;

class AssetSproutImportElementImporter extends BaseSproutImportElementImporter
{
	/**
	 * @return mixed
	 */
	public function defineModel()
	{
		return 'AssetModel';
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