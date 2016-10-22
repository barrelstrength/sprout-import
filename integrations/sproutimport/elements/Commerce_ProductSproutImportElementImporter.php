<?php
namespace Craft;

class Commerce_ProductSproutImportElementImporter extends BaseSproutImportElementImporter
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Craft Commerce Products');
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
			if (empty($this->rows['variants']))
			{
				$message = Craft::t('At least one variant is required');

				SproutImportPlugin::log($message, LogLevel::Error);

				sproutImport()->addError($message, 'variant-required');

				return false;
			}

			$variants = $this->rows['variants'];

			// Loop through each variant
			foreach ($variants as $variant => $attributes)
			{
				// Check if related variants need to be resolved
				if (strpos($variant, 'new') === 0 && isset($attributes['related']))
				{
					$variantFields = isset($attributes['fields']) ? $attributes['fields'] : array();
					$relatedFields = $attributes['related'];

					$variantFields = sproutImport()->elementImporter->resolveRelationships($relatedFields, $variantFields);

					if (!$variantFields)
					{
						return false;
					}

					unset($variants[$variant]['related']);

					$variants[$variant]['fields'] = $variantFields;
				}
			}

			\Commerce\Helpers\CommerceProductHelper::populateProductVariantModels($product, $variants);

			if (!craft()->commerce_products->saveProduct($product))
			{
				// If result is false, products errors will have already been logged
				// but we need to take an extra step to log the variant errors
				foreach ($product->getVariants() as $variant)
				{
					sproutImport()->addError(Craft::t('Product Variants have errors. See logs.'), 'variant-errors');

					SproutImportPlugin::log(array(
						'Variant has errors' => $variant->getErrors()
					));
				}

				return false;
			}
		}
		catch (\Exception $e)
		{
			SproutImportPlugin::log(Craft::t('Commerce Product Import Error: ') . $e->getMessage(), LogLevel::Error);

			sproutImport()->addError(Craft::t('Commerce Product Import Error: '), 'commerce-import-error');
			sproutImport()->addError($e->getMessage(), 'commerce-import-error-message');
		}
	}

	/**
	 * Delete an Element using the Element ID
	 *
	 * @param $id
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function deleteById($id)
	{
		$product     = new Commerce_ProductModel();
		$product->id = $id;

		return craft()->commerce_products->deleteProduct($product);
	}

	/**
	 * @return array
	 */
	public function getImporterDataKeys()
	{
		return array('variants');
	}
}