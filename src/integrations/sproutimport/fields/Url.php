<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\fields;

use barrelstrength\sproutbase\app\import\contracts\BaseFieldImporter;
use craft\fields\Url as UrlField;

class Url extends BaseFieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return UrlField::class;
    }

    /**
     * @return mixed
     */
    public function getMockData()
    {
        return $this->fakerService->url;
    }
}
