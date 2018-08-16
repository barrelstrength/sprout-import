<?php

namespace barrelstrength\sproutimport\importers\settings;

use barrelstrength\sproutbase\app\import\base\SettingsImporter;
use Craft;
use craft\models\UserGroup as UserGroupModel;
use craft\records\UserGroup as UserGroupRecord;

class UserGroup extends SettingsImporter
{
    /**
     * @var
     */
    public $isNewSection;
    /**
     * @return string
     */
    public function getName()
    {
        return Craft::t('sprout-import', 'User Group');
    }

    /**
     * @return string
     */
    public function getModelName()
    {
        return UserGroupModel::class;
    }

    /**
     * @inheritdoc
     */
    public function getRecordName()
    {
        return UserGroupRecord::class;
    }

    /**
     * @param $id
     *
     * @return bool|mixed
     * @throws \craft\errors\WrongEditionException
     */
    public function deleteById($id)
    {
        return Craft::$app->getUserGroups()->deleteGroupById($id);
    }

    /**
     * @return bool
     * @throws \craft\errors\WrongEditionException
     */
    public function save()
    {
        $this->isNewSection = $this->model->id ? false : true;

        return Craft::$app->getUserGroups()->saveGroup($this->model);
    }

    public function returnRelatedValue($params)
    {
        $recordClass = $this->getRecordName();
        $record = new $recordClass();

        $records = $record::findAll($params);

        $ids = null;
        if ($records) {
            foreach ($records as$record ) {
                $ids[] = $record->id;
            }
        }

        return $ids;
    }
}
