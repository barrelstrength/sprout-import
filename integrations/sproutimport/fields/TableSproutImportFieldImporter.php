<?php
namespace Craft;

class TableSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
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
			$values[] = sproutImport()->seed->generateColumns($columns);
		}

		return $values;
	}

}
