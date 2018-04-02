<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\fields;

use barrelstrength\sproutbase\contracts\sproutimport\BaseFieldImporter;
use craft\redactor\Field;

class Redactor extends BaseFieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return Field::class;
    }

    /**
     * @return mixed
     */
    public function getMockData()
    {
        $lines = random_int(3, 5);
        $paragraphs = $this->fakerService->paragraphs($lines);

        return implode("\n\n", $paragraphs);
    }
}
