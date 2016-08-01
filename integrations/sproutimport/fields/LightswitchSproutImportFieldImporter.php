<?php
namespace Craft;

class LightswitchSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'Lightswitch';
	}

	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->model->settings;

		$bool = rand(0, 1);

		if ($bool === 0)
		{
			$value = '';
		}

		return $bool;
	}
}
