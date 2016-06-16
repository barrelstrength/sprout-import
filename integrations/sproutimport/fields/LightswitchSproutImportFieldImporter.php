<?php
namespace Craft;

class LightswitchSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->fieldModel->settings;

		$bool = $this->fakerService->boolean;

		$value = 1;
		if ($bool === false)
		{
			$value = '';
		}

		return $value;
	}
}
