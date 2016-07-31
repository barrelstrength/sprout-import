<?php
namespace Craft;

class UsersSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getFieldTypeModelName()
	{
		return 'UsersFieldType';
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
		$sources  = $settings['sources'];

		$groupIds = sproutImport()->mockData->getElementGroupIds($sources);

		$attributes = array(
			'groupIds' => $groupIds
		);

		$elementIds = sproutImport()->mockData->getMockRelations("User", $attributes);

		return $elementIds;
	}
}
