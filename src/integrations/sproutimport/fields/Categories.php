<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\fields;

use barrelstrength\sproutbase\contracts\sproutimport\BaseFieldImporter;
use barrelstrength\sproutimport\SproutImport;
use craft\elements\Category;
use craft\fields\Categories as CategoriesField;
use Craft;

class Categories extends BaseFieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return CategoriesField::class;
    }

    /**
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getSeedSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-import/_seeds/categories/settings', [
            'settings' => $this->seedSettings['fields']['categories'] ?? []
        ]);
    }

    /**
     * @return array|bool|mixed
     */
    public function getMockData()
    {
        $settings = $this->model->settings;

        $relatedMin = 1;
        $relatedMax = 3;

        if (isset($this->seedSettings['fields']))
        {
            $relatedMin = $this->seedSettings['fields']['assets']['relatedMin'] ?: $relatedMin;
            $relatedMax = $this->seedSettings['fields']['assets']['relatedMax'] ?: $relatedMax;
        }

        $relatedMax = SproutImport::$app->fieldImporter->getLimit($settings['branchLimit'], $relatedMax);

        $mockDataSettings = [
            'fieldName' => $this->model->name,
            'required' => $this->model->required,
            'relatedMin' => $relatedMin,
            'relatedMax' => $relatedMax
        ];

        if (empty($settings['source'])) {
            SproutImport::info(Craft::t('sprout-import', 'Unable to generate Mock Data for relations field: {fieldName}. No Source found.', [
                'fieldName' => $this->model->name
            ]));
            return null;
        }

        $source = $settings['source'];

        $groupId = SproutImport::$app->fieldImporter->getElementGroupId($source);

        $attributes = null;

        if ($source != '*') {
            $attributes = [
                'groupId' => $groupId
            ];
        }

        $categoryElement = new Category();

        $elementIds = SproutImport::$app->fieldImporter->getMockRelations($categoryElement, $attributes, $mockDataSettings);

        return $elementIds;
    }
}
