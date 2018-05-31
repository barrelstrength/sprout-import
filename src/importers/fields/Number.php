<?php

namespace barrelstrength\sproutimport\importers\fields;

use barrelstrength\sproutbase\app\import\base\FieldImporter;
use craft\fields\Number as NumberField;
use Craft;

class Number extends FieldImporter
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
        return Craft::$app->getView()->renderTemplate('sprout-base-import/settings/seed-defaults/number/settings', [
            'settings' => $this->seedSettings['fields']['number'] ?? []
        ]);
    }

    /**
     * @return mixed
     */
    public function getMockData()
    {
        $min = 1;
        $max = 3;
        $decimals = 0;

        if (isset($this->seedSettings['fields']))
        {
            $min = $this->seedSettings['fields']['number']['min'] ?: $min;
            $max = $this->seedSettings['fields']['number']['max'] ?: $max;
            $decimals = $this->seedSettings['fields']['number']['decimals'] ?: $decimals;
        }

        $defaultMin = is_numeric($min) ? $min : 0;
        $defaultMax = is_numeric($max) ? $max : 100;
        $defaultDecimals = is_numeric($decimals) ? $decimals : 0;

        $min = $this->model->min ?? $defaultMin;
        $max = $this->model->max ?? $defaultMax;
        $decimals = $this->model->decimals ?? $defaultDecimals;

        if ($decimals > 0) {
            return $this->fakerService->randomFloat($decimals, $min, $max);
        }

        return $this->fakerService->numberBetween($min, $max);
    }
}
