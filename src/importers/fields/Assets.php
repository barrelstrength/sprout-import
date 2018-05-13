<?php

namespace barrelstrength\sproutimport\importers\fields;

use barrelstrength\sproutbase\app\import\base\FieldImporter;
use barrelstrength\sproutimport\SproutImport;
use craft\elements\Asset;
use Craft;
use craft\fields\Assets as AssetsField;

class Assets extends FieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return AssetsField::class;
    }

    /**
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getSeedSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-base-import/settings/seed-defaults/assets/settings', [
            'settings' => $this->seedSettings['fields']['assets'] ?? []
        ]);
    }

    /**
     * @return array|bool|mixed|null
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

        $relatedMax = SproutImport::$app->fieldImporter->getLimit($settings['limit'], $relatedMax);

        $mockDataSettings = [
            'fieldName' => $this->model->name,
            'required' => $this->model->required,
            'relatedMin' => $relatedMin,
            'relatedMax' => $relatedMax
        ];

        if (empty($settings['sources'])) {
            SproutImport::info(Craft::t('sprout-import', 'Unable to generate Mock Data for relations field: {fieldName}. No Sources found.', [
                'fieldName' => $this->model->name
            ]));
            return null;
        }

        $sources = $settings['sources'];

        $sourceIds = SproutImport::$app->fieldImporter->getElementGroupIds($sources);

        $attributes = null;

        if ($sources != '*') {
            $attributes = [
                'sourceId' => $sourceIds
            ];
        }

        $assetElement = new Asset();

        $elementIds = SproutImport::$app->fieldImporter->getMockRelations($assetElement, $attributes, $mockDataSettings);

        return $elementIds;
    }
}
