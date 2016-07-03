<?php
namespace Craft;

class TagsSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->fieldModel->settings;

		$source = $settings['source'];

		$groupId = sproutImport()->seed->getElementGroup($source);

		$find = array('groupId' => $groupId);

		$elementIds = sproutImport()->seed->getMockFieldElements("Tag", $find, '');

		return $elementIds;
	}
}
