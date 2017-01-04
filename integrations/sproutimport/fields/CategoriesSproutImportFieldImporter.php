<?php
namespace Craft;

class CategoriesSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'Categories';
	}

	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->model->settings;

		if (!isset($settings['source'])) return;

		$limit    = sproutImport()->mockData->getLimit($settings['limit'], 3);
		$source   = $settings['source'];

		$groupId = sproutImport()->mockData->getElementGroupId($source);

		$attributes = array(
			'groupId' => $groupId
		);

		$elementIds = sproutImport()->mockData->getMockRelations("Category", $attributes, $limit);

		return $elementIds;
	}
}
