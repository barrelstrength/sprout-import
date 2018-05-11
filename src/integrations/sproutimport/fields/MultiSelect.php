<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\fields;

use barrelstrength\sproutbase\app\import\contracts\BaseFieldImporter;
use barrelstrength\sproutimport\SproutImport;
use craft\fields\MultiSelect as MultiSelectField;

class MultiSelect extends BaseFieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return MultiSelectField::class;
    }

    /**
     * @return mixed
     */
    public function getMockData()
    {
        $settings = $this->model->settings;

        $values = [];

        if (!empty($settings['options'])) {
            $options = $settings['options'];

            $length = count($options);
            $number = random_int(1, $length);

            $randomArrayItems = SproutImport::$app->fieldImporter->getRandomArrayItems($options, $number);

            $values = SproutImport::$app->fieldImporter->getOptionValuesByKeys($randomArrayItems, $options);
        }

        return $values;
    }
}
