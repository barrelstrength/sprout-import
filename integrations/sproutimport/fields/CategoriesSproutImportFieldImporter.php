<?php
namespace Craft;

class CategoriesSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getFieldTypeModelName()
	{
		return 'CategoriesFieldType';
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
		$source   = $settings['source'];

		$groupId = sproutImport()->mockData->getElementGroupId($source);

		$attributes = array(
			'groupId' => $groupId
		);

		$elementIds = sproutImport()->mockData->getMockRelations("Category", $attributes, $limit);

		return $elementIds;
	}
}
