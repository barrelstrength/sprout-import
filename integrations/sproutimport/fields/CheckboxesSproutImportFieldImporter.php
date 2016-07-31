<?php
namespace Craft;

class CheckboxesSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getFieldTypeModelName()
	{
		return 'CheckboxesFieldType';
	}

	/**
	 * @return bool
	 */
	public function canMockData()
	{
		return true;
	}

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

			$randArrays = sproutImport()->mockData->getRandomArrays($options, $number);

			$values = sproutImport()->mockData->getOptionValuesByKeys($randArrays, $options);

			return $values;
		}
	}
}
