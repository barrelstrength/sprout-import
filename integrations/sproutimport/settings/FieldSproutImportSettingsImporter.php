<?php
namespace Craft;

class FieldSproutImportSettingsImporter extends BaseSproutImportSettingsImporter
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return "Field";
	}

	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'Field';
	}

	/**
	 * @param null $handle
	 *
	 * @return FieldModel|null
	 */
	public function getModelByHandle($handle = null)
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
				$message = $type . ' field importer not supported';

				SproutImportPlugin::log($message, LogLevel::Error);

				sproutImport()->addError($message, 'field-importer-null');

				return false;
			}
		}
		catch (\Exception $e)
		{
			SproutImportPlugin::log($e->getMessage(), LogLevel::Error);

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
