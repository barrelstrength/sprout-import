<?php
namespace Craft;

class AssetsSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return array
	 */
	public function getMockData()
	{
		$settings = $this->fieldModel->settings;

		$limit = $settings['limit'];

		$sourceIds = sproutImport()->seed->getFindElementSettings($settings);

		$find = array('sourceId' => $sourceIds);

		$elementIds = sproutImport()->seed->getMockFieldElements("Asset", $find, $limit);

		return $elementIds;
	}
}
