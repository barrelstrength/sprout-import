<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\fields;

use barrelstrength\sproutbase\app\import\contracts\BaseFieldImporter;
use craft\fields\Lightswitch as LightswitchField;

class Lightswitch extends BaseFieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return LightswitchField::class;
    }

    /**
     * Returns a boolean value
     *
     * @return mixed
     */
    public function getMockData()
    {
        return random_int(0, 1);
    }
}
