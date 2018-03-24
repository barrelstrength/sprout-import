<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\fields;

use barrelstrength\sproutbase\contracts\sproutimport\BaseFieldImporter;
use barrelstrength\sproutimport\SproutImport;
use craft\fields\RadioButtons as RadioButtonsField;

class RadioButtons extends BaseFieldImporter
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
