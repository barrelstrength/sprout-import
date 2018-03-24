<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\fields;

use barrelstrength\sproutbase\contracts\sproutimport\BaseFieldImporter;
use barrelstrength\sproutimport\SproutImport;
use craft\elements\User;
use craft\fields\Users as UsersField;
use Craft;

class Users extends BaseFieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return UsersField::class;
    }

    /**
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getSeedSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-import/_seeds/users/settings', [
            'settings' => $this->seedSettings['fields']['users'] ?? []
        ]);
    }

    /**
     * @return array|bool|mixed
     */
    public function getMockData()
    {
        $settings = $this->model->settings;

        $relatedMin = $this->seedSettings['fields']['users']['relatedMin'] ?: 1;
        $relatedMax = $this->seedSettings['fields']['users']['relatedMax'] ?: 3;

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

        $groupIds = SproutImport::$app->fieldImporter->getElementGroupIds($sources);
        $attributes = null;

        if ($sources != '*') {
            $attributes = [
                'groupIds' => $groupIds
            ];
        }

        $userElement = new User();

        $elementIds = SproutImport::$app->fieldImporter->getMockRelations($userElement, $attributes, $mockDataSettings);

        return $elementIds;
    }
}
