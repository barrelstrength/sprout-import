<?php
namespace Craft;

class Commerce_ProductTypeSproutImportSettingsImporter extends BaseSproutImportSettingsImporter
{
	private $defaultArgs = array(
		'hasVariantTitleField' => false,
		'titleFormat'          => '{product.title}'
	);

	/**
	 * @return string
	 */
	public function getName()
	{
		return "Craft Commerce Product Type";
	}

	/**
	 * @return mixed
	 */
	public function getModelName()
	{
		return 'Commerce_ProductType';
	}

	/**
	 * @return mixed
	 */
	public function save()
	{
		return craft()->commerce_productTypes->saveProductType($this->model);
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public function deleteById($id)
	{
		$product = craft()->commerce_productTypes->getProductTypeById($id);
		if ($product != null)
		{
			return craft()->commerce_productTypes->deleteProductTypeById($id);
		}
	}

	/**
	 * @param $model
	 * @param $settings
	 */
	public function setModel($model, $settings)
	{
		$attributes = array_merge($this->defaultArgs, $settings);

		if (isset($settings['urlFormat']))
		{
			$locales = [];

			foreach (craft()->i18n->getSiteLocaleIds() as $localeId)
			{
				$locales[$localeId] = new Commerce_ProductTypeLocaleModel([
					'locale'    => $localeId,
					'urlFormat' => $settings['urlFormat']
				]);
			}

			$model->setLocales($locales);
		}

		if (isset($settings['productFields']) && !empty($settings['productFields']))
		{
			foreach ($settings['productFields'] as $name => $fields)
			{
				$entryFields = sproutImport()->getFieldIdsByHandle($name, $fields);

				if (!empty($entryFields))
				{
					$fieldLayout       = craft()->fields->assembleLayout($entryFields);
					$fieldLayout->type = 'Commerce_Product';

					$model->asa('productFieldLayout')->setFieldLayout($fieldLayout);
				}
			}
		}

		$model->setAttributes($attributes);

		if (isset($settings['variantFields']["Content"]) && !empty($settings['variantFields']["Content"]))
		{
			$model->setAttribute('hasVariants', true);

			$entryVariantFields = array();

			foreach ($settings['variantFields']["Content"] as $field)
			{
				if (!is_numeric($field))
				{
					$fieldId = craft()->fields->getFieldByHandle($field)->id;
				}
				else
				{
					$fieldId = $field;
				}

				$entryVariantFields["Content"][] = $fieldId;
			}

			$variantFieldLayout       = craft()->fields->assembleLayout($entryVariantFields);
			$variantFieldLayout->type = 'Commerce_Variant';
			$model->asa('variantFieldLayout')->setFieldLayout($variantFieldLayout);
		}

		$this->model = $model;
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
}