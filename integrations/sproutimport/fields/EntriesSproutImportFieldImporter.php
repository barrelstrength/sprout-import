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
		$limit    = $settings['limit'];
		$sources  = $settings['sources'];

		$sectionIds = sproutImport()->mockData->getElementGroupIds($sources);

		$attributes = array(
			'sectionId' => $sectionIds
		);

		$elementIds = sproutImport()->mockData->getMockRelations("Entry", $attributes, $limit);

		return $elementIds;
	}
}
