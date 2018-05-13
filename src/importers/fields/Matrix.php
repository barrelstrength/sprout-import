<?php

namespace barrelstrength\sproutimport\importers\fields;

use barrelstrength\sproutbase\app\import\base\FieldImporter;
use barrelstrength\sproutimport\SproutImport;
use Craft;
use craft\fields\Matrix as MatrixField;

class Matrix extends FieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return MatrixField::class;
    }

    /**
     * @return mixed
     */
    public function getMockData()
    {
        $fieldId = $this->model->id;
        $blocks = Craft::$app->getMatrix()->getBlockTypesByFieldId($fieldId);

        $values = [];

        if (!empty($blocks)) {
            $count = 1;

            foreach ($blocks as $block) {
                $key = 'new'.$count;

                $values[$key] = [
                    'type' => $block->handle,
                    'enabled' => 1
                ];

                $fieldLayoutId = $block->fieldLayoutId;

                $fieldLayouts = Craft::$app->getFields()->getFieldsByLayoutId($fieldLayoutId);

                $values[$key]['fields'] = SproutImport::$app->fieldImporter->getFieldsWithMockData($fieldLayouts);

                $count++;
            }
        }

        return $values;
    }
}
