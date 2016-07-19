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
		$product  = $this->model;

		try
		{
			if (empty($this->data['variants']))
			{
				sproutImport()->errorLog('Variants input is required');

				return false;
			}

			$variants = $this->data['variants'];

			\Commerce\Helpers\CommerceProductHelper::populateProductVariantModels($product, $variants);

			return craft()->commerce_products->saveProduct($product);
		}
		catch (\Exception $e)
		{
			sproutImport()->errorLog('Commerce Product Import Error:');
			sproutImport()->errorLog($e->getMessage());
		}
	}

	/**
	 * @return string
	 */
	public function getSettingsHtml()
	{
	}

	/**
	 * @param $settings
	 */
	public function getMockData($settings)
	{
	}

	/**
	 * @return array
	 */
	public function defineKeys()
	{
		return array('variants');
	}
}