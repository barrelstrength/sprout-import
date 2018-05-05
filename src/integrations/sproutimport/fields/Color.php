<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\fields;

use barrelstrength\sproutbase\sproutimport\contracts\BaseFieldImporter;
use craft\fields\Color as ColorField;

class Color extends BaseFieldImporter
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
