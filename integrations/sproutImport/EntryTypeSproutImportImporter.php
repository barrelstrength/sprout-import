<?php
namespace Craft;

class EntryTypeSproutImportImporter extends BaseSproutImportImporter
{
	/**
	 * @return string
	 */
	public function getModel()
	{
		return 'Craft\\EntryTypeModel';
	}

	/**
	 * @param $model
	 * @param $settings
	 */
	public function populateModel($model, $settings)
	{
		// @TODO - require groupId or set fallback.

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
