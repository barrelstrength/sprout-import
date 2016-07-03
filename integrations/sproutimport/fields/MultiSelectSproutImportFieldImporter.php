<?php
namespace Craft;

class MultiSelectSproutImportFieldImporter extends BaseSproutImportFieldImporter
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

			$randArrays = sproutImport()->seed->getRandomArrays($options, $number);

			$values = sproutImport()->seed->getOptionValuesByKeys($randArrays, $options);

			return $values;
		}
	}
}
