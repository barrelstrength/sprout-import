<?php
namespace Craft;

class EntriesSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'Entries';
	}

	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->model->settings;
		$limit    = sproutImport()->mockData->getLimit($settings['limit'], 3);
		$sources  = $settings['sources'];

		$sectionIds = sproutImport()->mockData->getElementGroupIds($sources);

		$attributes = array(
			'sectionId' => $sectionIds
		);

		$elementIds = sproutImport()->mockData->getMockRelations("Entry", $attributes, $limit);

		return $elementIds;
	}
}
