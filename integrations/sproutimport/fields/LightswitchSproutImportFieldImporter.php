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

		$bool = rand(0,1);

		if ($bool === 0)
		{
			$value = '';
		}

		return $bool;
	}
}
