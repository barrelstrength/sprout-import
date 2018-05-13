<?php

namespace barrelstrength\sproutimport\importers\elements;

use barrelstrength\sproutbase\app\import\base\ElementImporter;
use barrelstrength\sproutimport\models\jobs\SeedJob;
use barrelstrength\sproutimport\SproutImport;
use Craft;
use craft\elements\Tag as TagElement;

class Tag extends ElementImporter
{
    /**
     * @var int
     */
    public $tagGroup;

    /**
     * @return mixed
     */
    public function getModelName()
    {
        return TagElement::class;
    }

    /**
     * @return bool
     */
    public function hasSeedGenerator()
    {
        return true;
    }

    /**
     * @param SeedJob $seedJob
     *
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getSeedSettingsHtml(SeedJob $seedJob)
    {
        $groupsSelect = [];

        $groups = Craft::$app->getTags()->getAllTagGroups();

        if (!empty($groups)) {
            foreach ($groups as $group) {
                $groupsSelect[$group->id]['label'] = $group->name;
                $groupsSelect[$group->id]['value'] = $group->id;
            }
        }

        return Craft::$app->getView()->renderTemplate('sprout-base-import/_components/importers/elements/seed-generators/Tag/settings', [
            'id' => $this->getModelName(),
            'tagGroups' => $groupsSelect,
            'seedJob' => $seedJob
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSeedSettingsErrors($settings)
    {
        if (isset($settings['tagGroup']) && empty($settings['tagGroup'])) {
            return Craft::t('sprout-import', 'Tag Group is required.');
        }

        return null;
    }

    /**
     * @param $quantity
     * @param $settings
     *
     * @return array
     */
    public function getMockData($quantity, $settings)
    {
        $data = [];
        $tagGroup = $settings['tagGroup'];

        if (!empty($quantity)) {
            for ($i = 1; $i <= $quantity; $i++) {
                $data[] = $this->generateTag($tagGroup);
            }
        }

        return $data;
    }

    /**
     * @param $tagGroup
     *
     * @return array
     */
    private function generateTag($tagGroup)
    {
        $faker = $this->fakerService;
        $name = $faker->word;

        $data = [];
        $data['@model'] = Tag::class;
        $data['attributes']['groupId'] = $tagGroup;
        $data['content']['title'] = $name;

        $this->tagGroup = $tagGroup;

        $fieldLayouts = $this->getFieldLayoutsByGroupId();

        $data['content']['fields'] = SproutImport::$app->fieldImporter->getFieldsWithMockData($fieldLayouts);

        return $data;
    }

    /**
     * Returns a Field Layout
     *
     * @return array|\craft\base\FieldInterface[]
     */
    private function getFieldLayoutsByGroupId()
    {
        $groupId = $this->tagGroup;

        $tagGroup = Craft::$app->getTags()->getTagGroupById($groupId);

        $fieldLayoutId = $tagGroup->fieldLayoutId;

        return Craft::$app->getFields()->getFieldsByLayoutId($fieldLayoutId);
    }

    /**
     * @param $model
     *
     * @return int|null
     */
    public function getFieldLayoutId($model)
    {
        $groupId = $model->groupId;

        $utilities = SproutImport::$app->utilities;

        if (($group = Craft::$app->getTags()->getTagGroupById($groupId)) === null) {
            $utilities->addError('invalid-tag-groupId', 'Invalid tag group ID: '.$groupId);
        }

        return $group->fieldLayoutId;
    }
}