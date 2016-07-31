<?php
namespace Craft;

class Commerce_ProductsSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getFieldTypeModelName()
	{
		return 'Commerce_ProductsFieldType';
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
		$sources  = $settings['sources'];

		$productTypeIds = sproutImport()->mockData->getElementGroupIds($sources);

		$attributes = array(
			'typeId' => $productTypeIds
		);

		$elementIds = sproutImport()->mockData->getMockRelations("Commerce_Product", $attributes, $limit);

		return $elementIds;
	}
}
