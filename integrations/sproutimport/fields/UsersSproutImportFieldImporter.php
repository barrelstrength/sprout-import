<?php
namespace Craft;

class UsersSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->fieldModel->settings;
		$limit    = $settings['limit'];
		$sources  = $settings['sources'];

		$groupIds = sproutImport()->mockData->getElementGroupIds($sources);

		$attributes = array(
			'groupIds' => $groupIds
		);

		$elementIds = sproutImport()->mockData->getMockRelations("User", $attributes, $limit);

		return $elementIds;
	}
}
