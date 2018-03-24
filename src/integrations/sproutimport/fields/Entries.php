<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\fields;

use barrelstrength\sproutbase\contracts\sproutimport\BaseFieldImporter;
use barrelstrength\sproutimport\SproutImport;
use craft\elements\Entry;
use craft\fields\Entries as EntriesField;
use Craft;

class Entries extends BaseFieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return EntriesField::class;
    }

    /**
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getSeedSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-import/_seeds/entries/settings', [
            'settings' => $this->seedSettings['fields']['entries'] ?? []
        ]);
    }

    /**
     * @return array|bool|mixed
     */
    public function getMockData()
    {
        $settings = $this->model->settings;

        $relatedMin = $this->seedSettings['fields']['entries']['relatedMin'] ?: 1;
        $relatedMax = $this->seedSettings['fields']['entries']['relatedMax'] ?: 3;

        $relatedMax = SproutImport::$app->fieldImporter->getLimit($settings['limit'], $relatedMax);

        $mockDataSettings = [
            'fieldName' => $this->model->name,
            'required' => $this->model->required,
            'relatedMin' => $relatedMin,
            'relatedMax' => $relatedMax
        ];

        if (!isset($settings['sources'])) {
            SproutImport::info(Craft::t('sprout-import', 'Unable to generate Mock Data for relations field: {fieldName}. No Sources found.', [
                'fieldName' => $this->model->name
            ]));
            return null;
        }

        $sources = $settings['sources'];

        $sectionIds = SproutImport::$app->fieldImporter->getElementGroupIds($sources);

        $attributes = null;

        if ($sources != '*') {
            $attributes = [
                'sectionId' => $sectionIds
            ];
        }

        $entryElement = new Entry();

        $elementIds = SproutImport::$app->fieldImporter->getMockRelations($entryElement, $attributes, $mockDataSettings);

        return $elementIds;
    }
}
