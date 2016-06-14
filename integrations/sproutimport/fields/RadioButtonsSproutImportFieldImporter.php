<?php
namespace Craft;

class RadioButtonsSproutImportFieldImporter extends BaseSproutImportFieldImporter
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

			$randKey = array_rand($options, 1);

			$value = $settings['options'][$randKey];

			return $value['value'];
		}
	}
}
