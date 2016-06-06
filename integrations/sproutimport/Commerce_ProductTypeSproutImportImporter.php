<?php
namespace Craft;

class Commerce_ProductTypeSproutImportImporter extends BaseSproutImportImporter
{
	private $defaultArgs = array(
		'hasVariantTitleField' => false,
		'titleFormat'          => '{product.title}'
	);

	/**
	 * @return mixed
	 */
	public function getModel()
	{
		$model = 'Craft\\Commerce_ProductTypeModel';

		return new $model;
	}

	public function populateModel($model, $settings)
	{
		$attributes = array_merge($this->defaultArgs, $settings);

		if (isset($settings['urlFormat']))
		{
			$locales = [];

			foreach (craft()->i18n->getSiteLocaleIds() as $localeId) {
				$locales[$localeId] = new Commerce_ProductTypeLocaleModel([
					'locale' => $localeId,
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
					$fieldLayout = craft()->fields->assembleLayout($entryFields);
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

			$variantFieldLayout = craft()->fields->assembleLayout($entryVariantFields);
			$variantFieldLayout->type = 'Commerce_Variant';
			$model->asa('variantFieldLayout')->setFieldLayout($variantFieldLayout);
		}



		$this->model = $model;
	}

	public function save()
	{
		return craft()->commerce_productTypes->saveProductType($this->model);
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

	public function deleteById($id)
	{
		return craft()->commerce_productTypes->deleteProductTypeById($id);
	}
}