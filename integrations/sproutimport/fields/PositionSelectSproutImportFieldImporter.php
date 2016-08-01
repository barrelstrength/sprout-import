<?php
namespace Craft;

class PositionSelectSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'PositionSelect';
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

			return sproutImport()->mockData->getRandomOptionValue($options, false);
		}
	}
}
