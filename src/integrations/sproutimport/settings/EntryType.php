<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\settings;

use barrelstrength\sproutbase\app\import\contracts\BaseSettingsImporter;
use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutforms\elements\Entry;
use barrelstrength\sproutimport\SproutImport;
use craft\models\EntryType as EntryTypeModel;
use Craft;

class EntryType extends BaseSettingsImporter
{
    /**
     * @return string
     */
    public function getName()
    {
        return Craft::t('sprout-import', 'Entry Type');
    }

    /**
     * @return string
     */
    public function getModelName()
    {
        return EntryTypeModel::class;
    }

    /**
     * @param EntryTypeModel $entryType
     * @param array          $rows
     *
     * @return mixed|void
     * @throws \Exception
     */
    public function setModel($entryType, array $rows = [])
    {
        // Set the simple stuff
        $entryType->sectionId = $rows['sectionId'];
        $entryType->name = $rows['name'];
        $entryType->handle = $rows['handle'];
        $entryType->hasTitleField = $rows['hasTitleField'] ?? true;
        $entryType->titleLabel = $rows['titleLabel'] ?? Craft::t('sprout-import', 'Title');
        $entryType->titleFormat = $rows['titleFormat'] ?? '';

        if (isset($rows['fieldLayout'])) {
            $fieldLayoutTabs = $rows['fieldLayout'];
            $fieldLayout = [];
            $requiredFields = [];

            foreach ($fieldLayoutTabs as $tab) {
                $tabName = $tab['name'];
                $fields = $tab['fields'];

                foreach ($fields as $fieldSettings) {
                    $model = SproutBase::$app->importers->getImporter($fieldSettings);

                    $field = SproutImport::$app->settingsImporter->saveSetting($fieldSettings, $model);

                    $fieldLayout[$tabName][] = $field->id;

                    if ($field->required) {
                        $requiredFields[] = $field->id;
                    }
                }
            }

            if ($entryType->getFieldLayout() != null) {
                // Remove previous field layout and update layout
                Craft::$app->getFields()->deleteLayoutById($entryType->fieldLayoutId);
            }

            $fieldLayout = Craft::$app->getFields()->assembleLayout($fieldLayout, $requiredFields);

            // Make Entry element as default
            $fieldLayout->type = empty($rows['elementType']) ? Entry::class : $rows['elementType'];

            $entryType->setFieldLayout($fieldLayout);
        }

        $this->model = $entryType;
    }

    /**
     * @return bool
     * @throws \Throwable
     * @throws \craft\errors\EntryTypeNotFoundException
     */
    public function save()
    {
        return Craft::$app->getSections()->saveEntryType($this->model);
    }

    /**
     * @param $id
     *
     * @return mixed|void
     */
    public function deleteById($id)
    {
        //return craft()->sections->deleteEntryTypeById($id);
    }

    /**
     * @param null $handle
     *
     * @return mixed
     */
    public function getModelByHandle($handle = null)
    {
        $types = Craft::$app->getSections()->getEntryTypesByHandle($handle);

        if (!empty($types)) {
            return $types[0];
        }

        return null;
    }
}
