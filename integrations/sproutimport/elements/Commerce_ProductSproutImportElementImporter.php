<?php
namespace Craft;

class Commerce_ProductSproutImportElementImporter extends BaseSproutImportElementImporter
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return "Craft Commerce Products";
	}

	/**
	 * @return mixed
	 */
	public function getModelName()
	{
		return 'Commerce_Product';
	}

	/**
	 * @return bool
	 * @throws Exception
	 * @throws \Exception
	 */
	public function save()
	{
		$product = $this->model;

		try
		{
			if (empty($this->data['variants']))
			{
				$message = Craft::t('Variants input is required');

				SproutImportPlugin::log($message, LogLevel::Error);

				sproutImport()->addError($message, 'variant-required');

				return false;
			}

			$variants = $this->data['variants'];

			\Commerce\Helpers\CommerceProductHelper::populateProductVariantModels($product, $variants);

			return craft()->commerce_products->saveProduct($product);
		}
		catch (\Exception $e)
		{
			SproutImportPlugin::log('Commerce Product Import Error:' . $e->getMessage(), LogLevel::Error);

			sproutImport()->addError('Commerce Product Import Error:', 'commerce-import-error');
			sproutImport()->addError($e->getMessage(), 'commerce-import-error-message');
		}
	}

	/**
	 * @return array
	 */
	public function defineKeys()
	{
		return array('variants');
	}
}