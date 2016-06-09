<?php
namespace Craft;

class Commerce_ProductSproutImportElementImporter extends BaseSproutImportElementImporter
{
	/**
	 * @return mixed
	 */
	public function defineModel()
	{
		return 'Commerce_ProductModel';
	}

	/**
	 * @return bool
	 * @throws Exception
	 * @throws \Exception
	 */
	public function save()
	{
		$product = $this->model;
		$variants = $this->data['variants'];

		try
		{
			\Commerce\Helpers\CommerceProductHelper::populateProductVariantModels($product, $variants);

			return craft()->commerce_products->saveProduct($product);
		}
		catch (\Exception $e)
		{
			sproutImport()->log('Commerce Product Import Error:');
			sproutImport()->log($e->getMessage());
		}

	}

	/**
	 * @return string
	 */
	public function getSettingsHtml()
	{

	}

	public function getMockData($settings)
	{

	}

	public function defineKeys()
	{
		return array('variants');
	}
}