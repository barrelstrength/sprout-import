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

		$limit  = $settings['limit'];
		$source = $settings['source'];

		$groupId = sproutImport()->seed->getElementGroup($source);

		$find = array('groupId' => $groupId);

		$elementIds = sproutImport()->seed->getMockFieldElements("Category", $find, $limit);

		return $elementIds;
	}
}
