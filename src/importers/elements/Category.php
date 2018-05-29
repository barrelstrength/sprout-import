<?php

namespace barrelstrength\sproutimport\importers\elements;

use barrelstrength\sproutimport\models\jobs\SeedJob;
use barrelstrength\sproutimport\SproutImport;
use Craft;
use barrelstrength\sproutbase\app\import\base\ElementImporter;
use craft\elements\Category as CategoryElement;

class Category extends ElementImporter
{
    private $categoryGroup;

    /**
     * @return mixed
     */
    public function getModelName()
    {
        return CategoryElement::class;
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

        $groups = Craft::$app->getCategories()->getAllGroups();

        if (!empty($groups)) {
            foreach ($groups as $group) {
                $groupsSelect[$group->id]['label'] = $group->name;
                $groupsSelect[$group->id]['value'] = $group->id;
            }
        }

        return Craft::$app->getView()->renderTemplate('sprout-base-import/_components/importers/elements/seed-generators/Category/settings', [
            'id' => $this->getModelName(),
            'categoryGroups' => $groupsSelect,
            'seedJob' => $seedJob
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSeedSettingsErrors($settings)
    {

        if (isset($settings['categoryGroup']) && empty($settings['categoryGroup'])) {
            return Craft::t('sprout-import', 'Category Group is required.');
        }

        return null;
    }

    public function setModel($model, array $settings = [])
    {
        parent::setModel($model, $settings);

        $slug = $settings['parentSlug'] ?? null;

        if ($slug) {
            $category = CategoryElement::findOne(['slug' => $slug]);

            if ($category) {
                $this->model->newParentId = $category->id;
            }
        }
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
        $categoryGroup = $settings['categoryGroup'];

        if (!empty($quantity)) {
            for ($i = 1; $i <= $quantity; $i++) {
                $generatedCategory = $this->generateCategory($categoryGroup);

                $data[] = $generatedCategory;
            }
        }

        return $data;
    }

    /**
     * @param $categoryGroup
     *
     * @return array
     */
    protected function generateCategory($categoryGroup)
    {
        $faker = $this->fakerService;
        $name = $faker->word;

        $data = [];
        $data['@model'] = Category::class;
        $data['attributes']['groupId'] = $categoryGroup;
        $data['content']['title'] = $name;

        $this->categoryGroup = $categoryGroup;

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
        $groupId = $this->categoryGroup;

        $categoryGroup = Craft::$app->getCategories()->getGroupById($groupId);

        $fieldLayoutId = $categoryGroup->fieldLayoutId;

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

        if (($group = Craft::$app->getCategories()->getGroupById($groupId)) === null) {
            $utilities->addError('invalid-category-groupId', 'Invalid category group ID: '.$groupId);
        }

        return $group->fieldLayoutId;
    }

    public function getImporterDataKeys()
    {
        return ['parentSlug'];
    }
}