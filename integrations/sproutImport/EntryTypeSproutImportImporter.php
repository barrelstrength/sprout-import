<?php
namespace Craft;

class EntryTypeSproutImportImporter extends SproutImportBaseImporter
{
	public function getModel()
	{
		$model = 'Craft\\EntryTypeModel';
		return new $model;
	}

	public function populateModel($model, $settings)
	{
		// @TODO - require groupId or set fallback.

		$model->setAttributes($settings);

		$this->model = $model;
	}

	public function save()
	{
		return craft()->sections->saveEntryType($this->model);
	}

	public function deleteById($id)
	{
		return craft()->sections->deleteEntryTypeById($id);
	}
}
