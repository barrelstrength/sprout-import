<?php
namespace Craft;

class Commerce_ProductsSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'Commerce_Products';
	}

	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->model->settings;
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
