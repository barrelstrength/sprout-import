<?php
namespace Craft;

class RadioButtonsSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'RadioButtons';
	}

	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->model->settings;

		if (!empty($settings['options']))
		{
			$options = $settings['options'];

			return sproutImport()->mockData->getRandomOptionValue($options);
		}
	}
}
