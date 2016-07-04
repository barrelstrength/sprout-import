<?php
namespace Craft;

class PositionSelectSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->fieldModel->settings;

		if (!empty($settings['options']))
		{
			$options = $settings['options'];

			return sproutImport()->mockData->getRandomOptionValue($options, false);
		}
	}
}
