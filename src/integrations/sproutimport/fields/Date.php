<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\fields;

use barrelstrength\sproutbase\contracts\sproutimport\BaseFieldImporter;
use barrelstrength\sproutimport\SproutImport;
use craft\fields\Date as DateField;

class Date extends BaseFieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return DateField::class;
    }

    /**
     * @return mixed
     */
    public function getMockData()
    {
        $settings = $this->model->settings;

        $minuteIncrement = $settings['minuteIncrement'];
        $showDate = $settings['showDate'];
        $showTime = $settings['showTime'];

        $values = [];

        $values['time'] = '';

        if ($showDate == true) {
            $values['date'] = $this->fakerService->date('d/m/Y');
        }

        if ($showTime == true) {
            $randomTimestamp = strtotime($this->fakerService->time('g:i:s A'));

            $values['time'] = SproutImport::$app->fieldImporter->getMinutesByIncrement($randomTimestamp, $minuteIncrement);
        }

        return $values;
    }
}
