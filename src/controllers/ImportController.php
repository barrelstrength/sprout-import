<?php

namespace barrelstrength\sproutimport\controllers;

use barrelstrength\sproutbase\app\import\base\Theme;
use barrelstrength\sproutimport\models\jobs\ImportJobs;
use barrelstrength\sproutimport\models\Json;
use barrelstrength\sproutimport\models\Seed;
use barrelstrength\sproutimport\queue\jobs\Import;
use barrelstrength\sproutimport\SproutImport;
use Craft;
use craft\helpers\FileHelper;
use craft\web\Controller;
use craft\web\UploadedFile;
use sproutimport\enums\ImportType;
use yii\base\ErrorException;
use yii\web\BadRequestHttpException;

class ImportController extends Controller
{
    /**
     * @throws BadRequestHttpException
     * @throws ErrorException
     */
    public function actionRunImport()
    {
        $this->requirePostRequest();

        // Prepare our variables
        $importDataString = Craft::$app->getRequest()->getBodyParam('importData');
        $uploadedFiles = UploadedFile::getInstancesByName('files');
        $seed = Craft::$app->getRequest()->getBodyParam('seed');

        // Prepare our Jobs
        $importJobs = new ImportJobs();

        $this->prepareUploadedFileImportJobs($importJobs, $uploadedFiles, $seed);
        $this->preparePostImportJobs($importJobs, $importDataString, $seed);

        // Queue our Jobs
        if (count($importJobs->jobs)) {

            try {
                foreach ($importJobs->jobs as $job) {
                    Craft::$app->queue->push(new Import([
                        'importData' => $job->importData,
                        'seedAttributes' => $job->seedAttributes
                    ]));
                }

                Craft::$app->getSession()->setNotice(Craft::t('sprout-import', '{count} job(s) queued for import.', [
                    'count' => count($importJobs->jobs)
                ]));
            } catch (\Exception $e) {
                $importJobs->addError('queue', $e->getMessage());

                SproutImport::error($e->getMessage());
            }
        } else {
            Craft::$app->getUrlManager()->setRouteParams([
                'importData' => $importDataString,
                'errors' => $importJobs->getErrors()
            ]);

            Craft::$app->getSession()->setError(Craft::t('sprout-import', 'Unable to queue import.'));
        }
    }

    /**
     * @throws BadRequestHttpException
     * @throws \yii\base\Exception
     */
    public function actionInstallTheme()
    {
        $this->requirePostRequest();

        $themeClassName = Craft::$app->getRequest()->getRequiredBodyParam('className');
        $seed = Craft::$app->getRequest()->getBodyParam('seed', false);

        /**
         * @var $theme Theme
         */
        $theme = new $themeClassName;
        $sourceFolder = $theme->getSourceTemplateFolder();
        $destinationFolder = $theme->getDestinationTemplateFolder();

        FileHelper::copyDirectory($sourceFolder, $destinationFolder);

        // Prepare our Jobs
        $importJobs = new ImportJobs();

        $importSchemaFolder = $theme->getSchemaFolder();
        $schemaFiles = FileHelper::findFiles($importSchemaFolder, [
            'recursive' => true
        ]);

        $this->prepareThemeFileImportJobs($importJobs, $schemaFiles, $seed);

        // Queue our Jobs
        if (count($importJobs->jobs)) {

            try {

                foreach ($importJobs->jobs as $job) {
                    Craft::$app->queue->push(new Import([
                        'importData' => $job->importData,
                        'seedAttributes' => $job->seedAttributes
                    ]));
                }

                Craft::$app->getSession()->setNotice(Craft::t('sprout-import', 'Importing theme.'));
            } catch (\Exception $e) {
                $importJobs->addError('queue', $e->getMessage());

                SproutImport::error($e->getMessage());
            }
        } else {

            SproutImport::error($importJobs->getErrors());

            Craft::$app->getUrlManager()->setRouteParams([
                'errors' => $importJobs->getErrors()
            ]);

            Craft::$app->getSession()->setError(Craft::t('sprout-import', 'Unable to import theme.'));
        }
    }

    /**
     * @param ImportJobs $importJobs
     * @param            $themeFiles
     * @param            $seed
     *
     * @return void
     */
    protected function prepareThemeFileImportJobs(ImportJobs $importJobs, $themeFiles, $seed)
    {
        if (!count($themeFiles)) {
            return;
        }

        $seedModel = new Seed();
        $seedModel->type = ImportType::Theme;
        $seedModel->enabled = (bool)$seed;

        foreach ($themeFiles as $filepath) {

            $fileContent = file_get_contents($filepath);

            if ($fileContent === false) {
                $errorMessage = Craft::t('sprout-import', 'Unable to import file: {filepath}', [
                    'filepath' => $filepath
                ]);
                $importJobs->addError('file', $errorMessage);
                SproutImport::error($errorMessage);
                break;
            }

            $jsonContent = new Json();
            $jsonContent->setJson($fileContent);

            // Make sure we have JSON
            if ($jsonContent->hasErrors()) {
                $importJobs->addError('json', $jsonContent->getFirstError('json'));
                SproutImport::error($jsonContent->getFirstError('json'));
                break;
            }

            $fileImportJob = new Import();
            $fileImportJob->seedAttributes = $seedModel->getAttributes();
            $fileImportJob->importData = $fileContent;

            $importJobs->addJob($fileImportJob);
        }
    }

    /**
     * @param ImportJobs $importJobs
     * @param            $uploadedFiles
     * @param            $seed
     *
     * @return void
     * @throws ErrorException
     */
    protected function prepareUploadedFileImportJobs(ImportJobs $importJobs, $uploadedFiles, $seed)
    {
        if (!count($uploadedFiles)) {
            return;
        }

        $seedModel = new Seed();
        $seedModel->type = ImportType::File;
        $seedModel->enabled = (bool)$seed;

        $tempFolderPath = SproutImport::$app->utilities->createTempFolder();

        foreach ($uploadedFiles as $file) {

            // Make sure our files don't have errors
            if ($file->getHasError()) {
                $importJobs->addError('file', $file->error);
                SproutImport::error($file->error);
                break;
            }

            $fileContent = file_get_contents($file->tempName);

            $jsonContent = new Json();
            $jsonContent->setJson($fileContent);

            // Make sure we have JSON
            if ($jsonContent->hasErrors()) {
                $importJobs->addError('json', $jsonContent->getFirstError('json'));
                SproutImport::error($jsonContent->getFirstError('json'));
                break;
            }

            $tempFilePath = $tempFolderPath.$file->name;

            // @todo - do we need to move the file if we've already got the content?
            // can we just add it to the jobs and delete it?
            if (move_uploaded_file($file->tempName, $tempFilePath)) {

                $fileImportJob = new Import();
                $fileImportJob->seedAttributes = $seedModel->getAttributes();
                $fileImportJob->importData = $fileContent;

                $importJobs->addJob($fileImportJob);

                // Delete temporary file
                // @todo - make sure we are removing files that have errors too
                FileHelper::unlink($tempFilePath);
            }
        }
    }

    /**
     * @param ImportJobs $importJobs
     * @param            $importDataString
     * @param            $seed
     *
     * @return void
     */
    public function preparePostImportJobs(ImportJobs $importJobs, $importDataString, $seed)
    {
        if (!$importDataString) {
            return;
        }

        $seedModel = new Seed();
        $seedModel->type = ImportType::CopyPaste;
        $seedModel->enabled = (bool)$seed;

        $importData = new Json();
        $importData->setJson($importDataString);

        // Make sure we have JSON
        if ($importData->hasErrors()) {

            $errorMessage = $importData->getFirstError('json');

            $importJobs->addError('json', $errorMessage);
            SproutImport::error($errorMessage);

            return;
        }

        $fileImportJob = new Import();
        $fileImportJob->seedAttributes = $seedModel->getAttributes();
        $fileImportJob->importData = $importData->json;

        $importJobs->addJob($fileImportJob);
    }
}
