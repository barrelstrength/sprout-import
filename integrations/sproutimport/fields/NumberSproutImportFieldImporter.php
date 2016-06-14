<?php
namespace Craft;

class NumberSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->fieldModel->settings;

		$min     = $settings['min'];
		$max     = $settings['max'];
		$decimal = $settings['decimals'];

		if (!empty($decimal))
		{
			return $this->fakerService->randomFloat($decimal, $min, $max);
		}

		return $this->fakerService->numberBetween($min, $max);
	}
}
