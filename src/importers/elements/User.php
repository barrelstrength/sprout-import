<?php

namespace barrelstrength\sproutimport\importers\elements;

use barrelstrength\sproutimport\models\jobs\SeedJob;
use barrelstrength\sproutimport\SproutImport;
use Craft;
use barrelstrength\sproutbase\app\import\base\ElementImporter;
use craft\elements\User as UserElement;

class User extends ElementImporter
{
    /**
     * @return mixed
     */
    public function getModelName()
    {
        return UserElement::class;
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
        $groups = Craft::$app->getUserGroups()->getAllGroups();

        $groupsSelect = [];

        if (!empty($groups)) {
            foreach ($groups as $group) {
                $groupsSelect[$group->id]['label'] = $group->name;
                $groupsSelect[$group->id]['value'] = $group->id;
            }
        }

        return Craft::$app->getView()->renderTemplate('sprout-base-import/_components/importers/elements/seed-generators/User/settings', [
            'id' => $this->getModelName(),
            'groups' => $groupsSelect,
            'seedJob' => $seedJob
        ]);
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
        $userGroup = $settings['userGroup'];

        if (!empty($quantity)) {
            for ($i = 1; $i <= $quantity; $i++) {
                $data[] = $this->generateUser($userGroup);
            }
        }

        return $data;
    }

    private function generateUser($groupId)
    {
        $faker = $this->fakerService;

        $firstName = $faker->firstName;
        $lastName = $faker->lastName;

        $username = $faker->userName;

        $username = SproutImport::$app->fieldImporter->generateUsernameOrEmail($username, $faker);

        $email = $faker->email;

        $email = SproutImport::$app->fieldImporter->generateUsernameOrEmail($email, $faker, true);

        $data = [];
        $data['@model'] = User::class;
        $data['attributes']['username'] = $username;
        $data['attributes']['firstName'] = $firstName;
        $data['attributes']['lastName'] = $lastName;
        $data['attributes']['email'] = $email;
        $data['attributes']['groups'] = [$groupId];

        $fieldLayout = Craft::$app->getFields()->getLayoutByType(UserElement::class);

        $fieldLayoutFieldModel = $fieldLayout->getFields();

        $data['content']['fields'] = SproutImport::$app->fieldImporter->getFieldsWithMockData($fieldLayoutFieldModel);

        return $data;
    }

    public function getFieldLayoutId($model)
    {

    }

    public function beforeValidateElement()
    {
        // If photoId is an array we matched it via a 'related' attribute and we should set
        // it to be an integer because photoId is an attribute and not like custom relation fields
        if (is_array($this->model->photoId) && count($this->model->photoId))
        {
            $this->model->photoId = $this->model->photoId[0];
        }
    }

    public function afterSaveElement()
    {
        if (count($this->model->getGroups()))
        {
            $groupIds = $this->model->getGroups();

            $userId = $this->model->id;

            Craft::$app->getUsers()->assignUserToGroups($userId, $groupIds);
        }
    }
}