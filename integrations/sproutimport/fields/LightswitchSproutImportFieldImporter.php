<?php
namespace Craft;

class LightswitchSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getFieldTypeModelName()
	{
		return 'LightswitchFieldType';
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

		$bool = rand(0, 1);

		if ($bool === 0)
		{
			$value = '';
		}

		return $bool;
	}
}
