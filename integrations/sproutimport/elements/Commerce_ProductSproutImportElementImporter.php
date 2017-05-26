<?php
namespace Craft;

class Commerce_ProductSproutImportElementImporter extends BaseSproutImportElementImporter
{
	private $productType;
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
	 */
	public function hasSeedGenerator()
	{
		return true;
	}

	/**
	 * @return string
	 */
	public function getSettingsHtml()
	{
		$productTypes = $this->getProductTypes();

		return craft()->templates->render('sproutimport/_integrations/commerce/settings', array(
			'id'       => $this->getModelName(),
			'productTypes' => $productTypes
		));
	}

	private function getProductTypes()
	{
		$products = array();

		$productTypes = craft()->commerce_productTypes->getAllProductTypes();

		if (!empty($productTypes))
		{
			foreach ($productTypes as $productType)
			{
				$products[] = new OptionData($productType->name, $productType->id, false);
			}
		}

		return $products;
	}

	public function getMockData($quantity, $settings)
	{
		$data = array();

		$productTypeId = $settings['productType'];

		$data[] = $this->generateProduct($productTypeId);

		return $data;
	}

	private function generateProduct($productTypeId)
	{
		$fakerDate = $this->fakerService->dateTimeThisYear('now');

		$taxIds = array();
		$taxCategories = craft()->commerce_taxCategories->getAllTaxCategories();

		$this->productType = craft()->commerce_productTypes->getProductTypeById($productTypeId);

		if (!empty($taxCategories))
		{
			foreach ($taxCategories as $taxCategory)
			{
				$taxIds[] = $taxCategory->id;
			}
		}

		$randomTaxKey = array_rand($taxIds);

		$shipIds = array();
		$shippingCategories = craft()->commerce_shippingCategories->getAllShippingCategories();

		if (!empty($shippingCategories))
		{
			foreach ($shippingCategories as $shipCategory)
			{
				$shipIds[] = $shipCategory->id;
			}
		}

		$randomShipKey = array_rand($shipIds);

		$product = array();
		$product['@model'] = "Commerce_ProductModel";
		$product['attributes']['typeId']        = $productTypeId;
		$product['attributes']['postDate']      = $fakerDate;
		$product['attributes']['enabled']       = 1;
		$product['attributes']['taxCategoryId'] = $taxIds[$randomTaxKey];
		$product['attributes']['shippingCategoryId'] = $shipIds[$randomShipKey];

		$product['attributes']['freeShipping'] = $this->fakerService->boolean;

		$product['attributes']['promotable']   = $this->fakerService->boolean;

		$fieldLayouts = $this->getFieldLayouts();

		$product['content']['fields'] = sproutImport()->mockData->getFieldsWithMockData($fieldLayouts);

		// maximum variant on a product is 5
		$variantsQty = $this->fakerService->numberBetween(1, 5);

		for ($rise = 1; $rise <= $variantsQty; $rise++)
		{
			$key = 'new' . $rise;
			$title = $this->fakerService->text(30);

			// Remove period on title
			$title = str_replace('.', '', $title);
			// Round off to nearest 10 or 5
			$randomPrice = ceil($this->fakerService->numberBetween(5, 1000) / 10) * 5;

			$product['content']['title']   = $title;

			$product['variants'][$key]['title'] = $title;
			$product['variants'][$key]['sku']   = $title;

			$product['variants'][$key]['price'] = $randomPrice;
			$product['variants'][$key]['unlimitedStock'] = 1;
			$product['variants'][$key]['minQty'] = 1;
			$product['variants'][$key]['maxQty'] = 100;
		}

		return $product;
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

	private function getFieldLayouts()
	{
		$fieldLayoutId = $this->productType->fieldLayoutId;

		$layouts = craft()->fields->getLayoutFieldsById($fieldLayoutId);

		return $layouts;
	}
}