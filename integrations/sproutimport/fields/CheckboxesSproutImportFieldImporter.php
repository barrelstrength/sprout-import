<?php
namespace Craft;

class CheckboxesSproutImportFieldImporter extends BaseSproutImportFieldImporter
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

			$length = count($options);
			$number = rand(1, $length);

			$randArrays = sproutImport()->getRandomArrays($options, $number);

			$values = sproutImport()->getOptionValuesByKeys($randArrays, $options);

			return $values;
		}
	}
}
