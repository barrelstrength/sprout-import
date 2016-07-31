<?php
namespace Craft;

class TableSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getFieldTypeModelName()
	{
		return 'TableFieldType';
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

		$columns = $settings['columns'];

		$randomLength = rand(2, 10);

		$values = array();

		for ($inc = 1; $inc <= $randomLength; $inc++)
		{
			$values[] = sproutImport()->mockData->generateColumns($columns);
		}

		return $values;
	}

}
