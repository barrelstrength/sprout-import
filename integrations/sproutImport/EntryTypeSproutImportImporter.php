<?php
namespace Craft;

class EntryTypeSproutImportImporter extends BaseSproutImportImporter
{
	/**
	 * @return string
	 */
	public function defineModel()
	{
		return 'Craft\\EntryTypeModel';
	}

	/**
	 * @param $model
	 * @param $settings
	 */
	public function populateModel($model, $settings)
	{
		$model->setAttributes($settings);

		$this->model = $model;
	}

	/**
	 * @return bool
	 * @throws Exception
	 * @throws \Exception
	 */
	public function save()
	{
		return craft()->sections->saveEntryType($this->model);
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function deleteById($id)
	{
		return craft()->sections->deleteEntryTypeById($id);
	}
}
