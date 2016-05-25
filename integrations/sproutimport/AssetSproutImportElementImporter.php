<?php
namespace Craft;

class AssetSproutImportElementImporter extends BaseSproutImportElementImporter
{
	/**
	 * @return mixed
	 */
	public function getModel()
	{
		$model = 'Craft\\AssetModel';

		return new $model;
	}

	public function save()
	{
		return craft()->assets->storeFile($this->model);
	}
}