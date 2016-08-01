<?php
namespace Craft;

class AssetFileSproutImportElementImporter extends BaseSproutImportElementImporter
{
	/**
	 * @return mixed
	 */
	public function getModelName()
	{
		return 'AssetFile';
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