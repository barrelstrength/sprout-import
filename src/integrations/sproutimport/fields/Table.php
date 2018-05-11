<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\fields;

use barrelstrength\sproutbase\app\import\contracts\BaseFieldImporter;
use barrelstrength\sproutimport\SproutImport;
use craft\fields\Table as TableField;

class Table extends BaseFieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return TableField::class;
    }

    /**
     * @return array|mixed|null
     */
    public function getMockData()
    {
        $settings = $this->model->settings;

        if (!isset($settings['columns'])) {
            return null;
        }

        $columns = $settings['columns'];

        $randomLength = random_int(2, 5);

        $values = [];

        for ($inc = 1; $inc <= $randomLength; $inc++) {
            $values[] = SproutImport::$app->fieldImporter->generateTableColumns($columns);
        }

        return $values;
    }

}
