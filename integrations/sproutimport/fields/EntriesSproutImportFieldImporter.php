<?php
namespace Craft;

class EntriesSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->fieldModel->settings;

		$sources      = $settings['sources'];
		$limit        = $settings['limit'];
		$sectionLabel = $settings['selectionLabel'];


	}
}
