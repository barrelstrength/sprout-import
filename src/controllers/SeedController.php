<?php

namespace barrelstrength\sproutimport\controllers;

use barrelstrength\sproutimport\integrations\sproutimport\elements\Category;
use barrelstrength\sproutimport\integrations\sproutimport\elements\Entry;
use barrelstrength\sproutimport\integrations\sproutimport\elements\Tag;
use barrelstrength\sproutimport\integrations\sproutimport\elements\User;
use barrelstrength\sproutimport\models\jobs\SeedJob;
use barrelstrength\sproutimport\SproutImport;
use craft\helpers\DateTimeHelper;
use craft\web\Controller;
use Craft;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class SeedController extends Controller
{
    /**
     * @param SeedJob $seedJob
     *
     * @return Response
     */
    public function actionSeedIndex(SeedJob $seedJob = null): Response
    {
        if ($seedJob === null) {
            $seedJob = new SeedJob();
        }

        $elementSelect = [];

        $allSeedImporters = SproutImport::$app->importers->getSproutImportSeedImporters();

        $defaultKeys = [
            Entry::class,
            Category::class,
            Tag::class,
            User::class
        ];

        $defaultSeedImporters = [];
        $customSeedImporters = [];

        if ($allSeedImporters) {
            foreach ($allSeedImporters as $key => $allSeedImporter) {
                if (in_array($key, $defaultKeys)) {
                    $defaultSeedImporters[$key] = $allSeedImporter;
                } else {
                    $customSeedImporters[$key] = $allSeedImporter;
                }
            }
        }

        if (!empty($defaultSeedImporters)) {
            $elementSelect['standard-elements'] = [
                'optgroup' => Craft::t('sprout-import', 'Standard Elements')
            ];

            foreach ($defaultSeedImporters as $importer) {
                $classNameSpace = get_class($importer);
                $title = $importer->getName();

                $elementSelect[$classNameSpace] = [
                    'label' => $title,
                    'value' => $classNameSpace
                ];
            }
        }

        if (!empty($customSeedImporters)) {
            $elementSelect['custom-elements'] = [
                'optgroup' => Craft::t('sprout-import', 'Custom Elements')
            ];

            foreach ($customSeedImporters as $importer) {

                $classNameSpace = get_class($importer);

                $title = $importer->getName();

                $elementSelect[$classNameSpace] = [
                    'label' => $title,
                    'value' => $classNameSpace
                ];
            }
        }

        return $this->renderTemplate('sprout-import/seed', [
            'elements' => $elementSelect,
            'importers' => $allSeedImporters,
            'seedJob' => $seedJob
        ]);
    }

    /**
     * Generates Elements with mock data and mark them as Seeds
     *
     * @throws BadRequestHttpException
     */
    public function actionGenerateElementSeeds()
    {
        $this->requirePostRequest();

        $elementType = Craft::$app->getRequest()->getRequiredBodyParam('elementType');
        $quantity = Craft::$app->getRequest()->getBodyParam('quantity');
        $settings = Craft::$app->getRequest()->getBodyParam('settings');

        $weedMessage = Craft::t('sprout-import', '{elementType} Element(s)');

        $details = Craft::t('sprout-import', $weedMessage, [
            'elementType' => $elementType
        ]);

        $seedJob = new SeedJob();
        $seedJob->elementType = $elementType;
        $seedJob->quantity = !empty($quantity) ? $quantity : 11;
        $seedJob->settings = $settings;
        $seedJob->type = 'Seed';
        $seedJob->details = $details;
        $seedJob->dateCreated = DateTimeHelper::currentUTCDateTime();

        if (!SproutImport::$app->seed->generateSeeds($seedJob))
        {
            $message = Craft::t('sprout-import', 'Unable to plant seeds.');

            Craft::$app->getSession()->setError($message);

            SproutImport::error($seedJob->getErrors());

            Craft::$app->getUrlManager()->setRouteParams([
                'seedJob' => $seedJob
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('sprout-import', '{quantity} Element(s) queued for seeding.', [
            'quantity' => $quantity
        ]));

        return $this->redirectToPostedUrl($seedJob);

    }
}
