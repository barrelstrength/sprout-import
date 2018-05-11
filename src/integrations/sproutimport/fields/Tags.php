<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\fields;

use barrelstrength\sproutbase\app\import\contracts\BaseFieldImporter;
use barrelstrength\sproutimport\SproutImport;
use craft\elements\Tag;
use craft\fields\Tags as TagsField;
use Craft;

class Tags extends BaseFieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return TagsField::class;
    }

    /**
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getSeedSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-import/_seeds/tags/settings', [
            'settings' => $this->seedSettings['fields']['tags'] ?? []
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

        $tagElement = new Tag();

        $elementIds = SproutImport::$app->fieldImporter->getMockRelations($tagElement, $attributes, $mockDataSettings);

        return $elementIds;
    }
}
