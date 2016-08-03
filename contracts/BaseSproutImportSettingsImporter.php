<?php
namespace Craft;

abstract class BaseSproutImportSettingsImporter extends BaseSproutImportImporter
{
	/**
	 * @return bool
	 */
	public function isSettings()
	{
		return true;
	}

	/**
	 * @return null
	 */
	public function getModel()
	{
		if (!$this->model)
		{
			$model = null;

			// If we have a Settings handle, use it to get our Settings Model
			if (isset($this->settings['handle']))
			{
				$handle = $this->settings['handle'];
				$model = $this->getModelByHandle($handle);
			}

			// If the Settings handle doesn't return anything, it doesn't exist yet
			// So just create a generic settings model
			if (!$model)
			{
				$model = parent::getModel();
			}

			$this->model = $model;
		}

		return $this->model;
	}

	/**
	 * A generic method that allows you to define how to retrieve the model of
	 * an importable data type using it's handle.
	 *
	 * In the case for importing fields, this is the getFieldByHandle method in the Fields Service.
	 * In the case for importing sections, this is the getSectionByHandle method in the Sections Service.
	 *
	 * @param null $handle
	 *
	 * @return null
	 */
	public function getModelByHandle($handle = null)
	{
		return null;
	}

	/**
	 * @return string
	 */
	abstract public function save();

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	abstract public function deleteById($id);
}