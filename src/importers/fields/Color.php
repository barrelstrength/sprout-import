<?php

namespace barrelstrength\sproutimport\importers\fields;

use barrelstrength\sproutbase\app\import\base\FieldImporter;
use craft\fields\Color as ColorField;

class Color extends FieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return ColorField::class;
    }

    /**
     * @return mixed
     */
    public function getMockData()
    {
        return $this->fakerService->hexColor;
    }
}
