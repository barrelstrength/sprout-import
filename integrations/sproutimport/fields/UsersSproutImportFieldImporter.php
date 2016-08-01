<?php
namespace Craft;

class UsersSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'Users';
	}

	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->model->settings;
		$sources  = $settings['sources'];

		$groupIds = sproutImport()->mockData->getElementGroupIds($sources);

		$attributes = array(
			'groupIds' => $groupIds
		);

		$elementIds = sproutImport()->mockData->getMockRelations("User", $attributes);

		return $elementIds;
	}
}
