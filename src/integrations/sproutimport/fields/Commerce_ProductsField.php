<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\fields;

use barrelstrength\sproutbase\app\import\contracts\BaseFieldImporter;
use barrelstrength\sproutimport\SproutImport;
use Craft;

class Commerce_ProductsField extends BaseFieldImporter
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

        $relatedMin = $this->seedSettings['commerceProductsField']['relatedMin'];
        $relatedMax = $this->seedSettings['commerceProductsField']['relatedMax'];

        $relatedMax = SproutImport::$app->fieldImporter->getLimit($settings['limit'], $relatedMax);

        $mockDataSettings = [
            'fieldName' => $this->model->name,
            'required' => $this->model->required,
            'relatedMin' => $relatedMin,
            'relatedMax' => $relatedMax
        ];

        $sources = $settings['sources'];

        if (!isset($settings['sources'])) {
            SproutImport::info(Craft::t('sprout-import', 'Unable to generate Mock Data for relations field: {fieldName}. No Sources found.', [
                'fieldName' => $this->model->name
            ]));
            return null;
        }

        $productTypeIds = SproutImport::$app->fieldImporter->getElementGroupIds($sources);

        $attributes = [
            'typeId' => $productTypeIds
        ];

        // @todo - update to new classname once Craft Commerce is released
        $elementIds = SproutImport::$app->fieldImporter->getMockRelations('Commerce_Product', $attributes, $mockDataSettings);

        return $elementIds;
    }
}
