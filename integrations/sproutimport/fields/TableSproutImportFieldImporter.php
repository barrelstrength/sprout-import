<?php
namespace Craft;

class TableSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'Table';
	}

	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->model->settings;

		$columns = $settings['columns'];

		$randomLength = rand(2, 5);

		$values = array();

		for ($inc = 1; $inc <= $randomLength; $inc++)
		{
			$values[] = sproutImport()->mockData->generateTableColumns($columns);
		}

		return $values;
	}

}
