<?php
namespace Craft;

class MultiSelectSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'MultiSelect';
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

			$length = count($options);
			$number = rand(1, $length);

			$randomArrayItems = sproutImport()->mockData->getRandomArrayItems($options, $number);

			$values = sproutImport()->mockData->getOptionValuesByKeys($randomArrayItems, $options);

			return $values;
		}
	}
}
