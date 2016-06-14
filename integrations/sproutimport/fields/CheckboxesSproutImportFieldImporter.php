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

			$results = sproutImport()->getOptionValuesByKeys($randArrays, $options);
			
			return $results;
		}
	}
}
