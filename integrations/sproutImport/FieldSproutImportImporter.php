<?php
namespace Craft;

class FieldSproutImportImporter extends BaseSproutImportImporter
{
	/**
	 * @return string
	 */
	public function defineModel()
	{
		return 'FieldModel';
	}

	/**
	 * @param null $handle
	 *
	 * @return FieldModel|null
	 */
	public function getObjectByHandle($handle = null)
	{
		return craft()->fields->getFieldByHandle($handle);
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function save()
	{
		return craft()->fields->saveField($this->model);
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 */
	public function deleteById($id)
	{
		return craft()->fields->deleteFieldById($id);
	}
}
