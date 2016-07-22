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
				sproutImport()->addError('Variants input is required', 'variant-required');

				return false;
			}

			$variants = $this->data['variants'];

			\Commerce\Helpers\CommerceProductHelper::populateProductVariantModels($product, $variants);

			return craft()->commerce_products->saveProduct($product);
		}
		catch (\Exception $e)
		{
			sproutImport()->addError('Commerce Product Import Error:', 'commerce-import-error');
			sproutImport()->addError($e->getMessage(), 'commerce-import-error-message');
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