<?php
namespace Craft;

class TagsSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'Tags';
	}

	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->model->settings;

		if (!isset($settings['source'])) return;

		$source = $settings['source'];

		$groupId = sproutImport()->mockData->getElementGroupId($source);

		$attributes = array(
			'groupId' => $groupId
		);

		$elementIds = sproutImport()->mockData->getMockRelations("Tag", $attributes, 3);

		return $elementIds;
	}
}
