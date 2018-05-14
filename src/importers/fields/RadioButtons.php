<?php

namespace barrelstrength\sproutimport\importers\fields;

use barrelstrength\sproutbase\app\import\base\FieldImporter;
use barrelstrength\sproutimport\SproutImport;
use craft\fields\RadioButtons as RadioButtonsField;

class RadioButtons extends FieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return RadioButtonsField::class;
    }

    /**
     * @return mixed
     */
    public function getMockData()
    {
        $settings = $this->model->settings;

        $radioValue = '';

        if (!empty($settings['options'])) {
            $options = $settings['options'];

            $radioValue = SproutImport::$app->fieldImporter->getRandomOptionValue($options);
        }

        return $radioValue;
    }
}
