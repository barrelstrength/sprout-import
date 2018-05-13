<?php

namespace barrelstrength\sproutimport\importers\fields;

use barrelstrength\sproutbase\app\import\base\FieldImporter;
use barrelstrength\sproutimport\SproutImport;
use craft\fields\Dropdown as DropdownField;

class Dropdown extends FieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return DropdownField::class;
    }

    /**
     * @return mixed
     */
    public function getMockData()
    {
        $settings = $this->model->settings;

        $optionValue = '';

        if (!empty($settings['options'])) {
            $options = $settings['options'];

            $optionValue = SproutImport::$app->fieldImporter->getRandomOptionValue($options);
        }

        return $optionValue;
    }
}