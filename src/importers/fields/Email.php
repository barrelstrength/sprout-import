<?php

namespace barrelstrength\sproutimport\importers\fields;

use barrelstrength\sproutbase\app\import\base\FieldImporter;
use craft\fields\Email as EmailField;

class Email extends FieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return EmailField::class;
    }

    /**
     * @return mixed
     */
    public function getMockData()
    {
        return $this->fakerService->email;
    }
}
