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

		$limit        = $settings['limit'];
		$sectionLabel = $settings['selectionLabel'];

		$sectionIds = sproutImport()->seed->getFindElementSettings($settings);

		$find = array('sectionId' => $sectionIds);

		$elementIds = sproutImport()->seed->getMockFieldElements("Entry", $find, $limit);

		return $elementIds;
	}
}
