<?php

namespace barrelstrength\sproutimport\controllers;

use barrelstrength\sproutseo\integrations\sproutimport\importers\elements\Redirect as RedirectImporter;
use Craft;
use yii\web\Controller;

class SproutSeoController extends Controller
{
    /**
     * Generate Redirect JSON in Sprout Import Format
     *
     * @throws \craft\errors\MissingComponentException
     */
    public function actionGenerateRedirectJson()
    {
        $pastedCSV = Craft::$app->getRequest()->post('pastedCSV');

        $importableJson = $this->convertToJson($pastedCSV);

        // Format: /old-url, /new-url, 301, 0
        if (!empty($importableJson)) {
            Craft::$app->getSession()->setNotice(Craft::t('sprout-import', 'Redirect JSON generated.'));

            $params = [
                'importableJson' => $importableJson
            ];

            Craft::$app->getUrlManager()->setRouteParams([
                'params' => $params
            ]);
        } else {
            Craft::$app->getSession()->setError(Craft::t('sprout-import', 'Unable to convert data.'));

            Craft::$app->urlManager->setRouteParams([
                'errors' => [
                    0 => Craft::t('sprout-import', 'CSV data not provided or using incorrect format.')
                ],
                'pastedCSV' => $pastedCSV
            ]);
        }
    }

    /**
     * Convert provided CSV data into JSON
     *
     * @param $csv
     *
     * @return string
     */
    private function convertToJson($csv)
    {
        $json = '';

        $array = array_map('str_getcsv', explode("\n", trim($csv)));

        if (is_array($array)) {
            $first = $array[0];
            $first = array_map('trim', $first);

            if ($this->isHeader($first) === true) {
                array_shift($array);
            }
        }

        $sproutSeoImportJson = [];

        foreach ($array as $key => $attributes) {
            $attributes = array_map('trim', $attributes);

            if (count($attributes) == 5) {
                $sproutSeoImportJson[$key]['@model'] = RedirectImporter::class;
                $sproutSeoImportJson[$key]['attributes'] = [
                    'oldUrl' => $attributes[0],
                    'newUrl' => $attributes[1],
                    'method' => $attributes[2],
                    'regex' => $attributes[3],
                    'count' => $attributes[4]
                ];
            }
        }

        if (!empty($sproutSeoImportJson)) {
            $json = json_encode($sproutSeoImportJson, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }

        return $json;
    }

    /**
     * Determine if the row might be our header row and remove it
     *
     * @param $header
     *
     * @return bool
     */
    private function isHeader($header)
    {
        $result = false;

        if (count($header) != 5) {
            return false;
        }

        if (
            $header[0] === 'oldUrl' ||
            $header[1] === 'newUrl' ||
            $header[2] === 'method' ||
            $header[3] === 'regex' ||
            $header[4] === 'count'
        ) {
            $result = true;
        }

        return $result;
    }
}