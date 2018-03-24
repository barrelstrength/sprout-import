<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\fields;

use barrelstrength\sproutbase\contracts\sproutimport\BaseFieldImporter;
use craft\fields\Number as NumberField;
use Craft;

class Number extends BaseFieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return NumberField::class;
    }

    /**
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getSeedSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-import/_seeds/number/settings', [
            'settings' => $this->seedSettings['fields']['number'] ?? []
        ]);
    }

    /**
     * @return mixed
     */
    public function getMockData()
    {
        $min = $this->seedSettings['fields']['number']['min'] ?? 0;
        $max = $this->seedSettings['fields']['number']['max'] ?? 100;
        $decimals = $this->seedSettings['fields']['number']['decimals'] ?? 0;

        $min = is_numeric($min) ? $min : 0;
        $max = is_numeric($max) ? $max : 100;
        $decimals = is_numeric($decimals) ? $decimals : 0;

        if ($decimals > 0) {
            return $this->fakerService->randomFloat($decimals, $min, $max);
        }

        return $this->fakerService->numberBetween($min, $max);
    }
}
