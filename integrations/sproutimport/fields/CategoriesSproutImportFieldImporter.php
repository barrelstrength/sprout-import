<?php
namespace Craft;

class CategoriesSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->fieldModel->settings;
		$limit    = $settings['limit'];
		$source   = $settings['source'];

		$groupId = sproutImport()->seed->getElementGroupId($source);

		$attributes = array(
			'groupId' => $groupId
		);

		$elementIds = sproutImport()->seed->getMockRelations("Category", $attributes, $limit);

		return $elementIds;
	}
}
