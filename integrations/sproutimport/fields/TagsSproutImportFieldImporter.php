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

		$groupId = sproutImport()->seed->getElementGroupId($source);

		$attributes = array(
			'groupId' => $groupId
		);

		$elementIds = sproutImport()->seed->getMockRelations("Tag", $attributes, '');

		return $elementIds;
	}
}
