<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\fields;

use barrelstrength\sproutbase\app\import\contracts\BaseFieldImporter;
use craft\fields\Email as EmailField;

class Email extends BaseFieldImporter
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
