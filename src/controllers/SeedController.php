<?php

namespace barrelstrength\sproutimport\controllers;

use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutimport\importers\elements\Category;
use barrelstrength\sproutimport\importers\elements\Entry;
use barrelstrength\sproutimport\importers\elements\Tag;
use barrelstrength\sproutimport\importers\elements\User;
use barrelstrength\sproutimport\models\jobs\SeedJob;
use barrelstrength\sproutimport\SproutImport;
use craft\helpers\DateTimeHelper;
use craft\web\Controller;
use Craft;
use barrelstrength\sproutbase\app\import\enums\ImportType;
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

        $allSeedImporters = SproutBase::$app->importers->getSproutImportSeedImporters();

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
                if (in_array($key, $defaultKeys, false)) {
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

        return $this->renderTemplate('sprout-base-import/seed/index', [
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
        $seedJob->seedType = ImportType::Seed;
        $seedJob->details = $details;
        $seedJob->dateCreated = DateTimeHelper::currentUTCDateTime();

        $seedJobErrors = null;

        if (!SproutImport::$app->seed->generateSeeds($seedJob)) {

            $seedJobErrors = $seedJob->getErrors();

            SproutBase::error($seedJobErrors);
        }

        $errors = SproutBase::$app->importUtilities->getErrors();

        if (!empty($errors) || $seedJobErrors != null) {
            $message = Craft::t('sprout-import', 'Unable to plant seeds.');

            Craft::$app->getSession()->setError($message);

            SproutImport::error($errors);

            Craft::$app->getUrlManager()->setRouteParams([
                'seedJob' => $seedJob
            ]);

            return false;
        }

        Craft::$app->getSession()->setNotice(Craft::t('sprout-import', '{quantity} Element(s) queued for seeding.', [
            'quantity' => $quantity
        ]));

        return $this->redirectToPostedUrl($seedJob);
    }
}
