<?php
namespace Craft;

class AssetsSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'Assets';
	}

	/**
	 * @return array
	 */
	public function getMockData()
	{
		$settings = $this->model->settings;

		if (!isset($settings['sources'])) return;

		$limit    = sproutImport()->mockData->getLimit($settings['limit'], 3);
		$sources  = $settings['sources'];

		$sourceIds = sproutImport()->mockData->getElementGroupIds($sources);

		$attributes = array(
			'sourceId' => $sourceIds
		);

		$elementIds = sproutImport()->mockData->getMockRelations("Asset", $attributes, $limit);

		return $elementIds;
	}
}
