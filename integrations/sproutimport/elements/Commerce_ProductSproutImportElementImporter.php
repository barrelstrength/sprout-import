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
				$message = Craft::t('At least one variant is required');

				SproutImportPlugin::log($message, LogLevel::Error);

				sproutImport()->addError($message, 'variant-required');

				return false;
			}

			$variants = $this->data['variants'];

			\Commerce\Helpers\CommerceProductHelper::populateProductVariantModels($product, $variants);

			if (!craft()->commerce_products->saveProduct($product))
			{
				// If result is false, products errors will have already been logged
				// but we need to take an extra step to log the variant errors
				foreach ($product->getVariants() as $variant)
				{
					sproutImport()->addError("Product Variants have errors. See logs.", 'variant-errors');

					SproutImportPlugin::log(array(
						'Variant has errors' => $variant->getErrors()
					));
				}

				return false;
			}

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
	public function getImporterDataKeys()
	{
		return array('variants');
	}
}