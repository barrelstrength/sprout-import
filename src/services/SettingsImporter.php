<?php

namespace barrelstrength\sproutimport\services;

use barrelstrength\sproutbase\contracts\sproutimport\BaseSettingsImporter;
use barrelstrength\sproutimport\SproutImport;
use craft\base\Component;
use Craft;

class SettingsImporter extends Component
{
    /**
     * @param                           $rows
     * @param BaseSettingsImporter|null $importerClass
     *
     * @return bool|\craft\base\Model|mixed|null
     * @throws \Exception
     */
    public function saveSetting($rows, BaseSettingsImporter $importerClass = null)
    {
        $model = $importerClass->getModel();

        if (!$model->validate(null, false)) {

            SproutImport::error(Craft::t('sprout-import', 'Errors found on model while saving Settings'));

            SproutImport::$app->utilities->addError('invalid-model', $model->getErrors());

            return false;
        }

        try {

            if ($importerClass->save()) {
                // Get updated model after save
                $model = $importerClass->getModel();

                $importerClass->resolveNestedSettings($model, $rows);

                return $model;
            }

            return false;
        } catch (\Exception $e) {

            $message = Craft::t('sprout-import', 'Unable to import Settings.');

            SproutImport::error($message);
            SproutImport::error($e->getMessage());

            SproutImport::$app->utilities->addError('save-setting-importer', $message);

            return false;
        }
    }
}