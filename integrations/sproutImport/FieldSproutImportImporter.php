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
		try
		{
			$type = $this->model['type'];
			$field = craft()->fields->getFieldType($type);

			if ($field != null)
			{
				if ($this->model->settings == null)
				{
					// Some fields require options setting to work
					$this->model->settings = array('options' => array());
				}

				return craft()->fields->saveField($this->model);
			}
			else
			{
				sproutImport()->addError($type . ' field importer not supported', 'field-importer-null');

				return false;
			}
		}
		catch (\Exception $e)
		{
			sproutImport()->addError($e->getMessage(), 'field-importer-error');

			return false;
		}

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
