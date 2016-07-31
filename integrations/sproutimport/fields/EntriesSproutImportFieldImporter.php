<?php
namespace Craft;

class EntriesSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getFieldTypeModelName()
	{
		return 'EntriesFieldType';
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
