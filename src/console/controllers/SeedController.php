<?php
namespace barrelstrength\sproutimport\console\controllers;

use yii\console\Controller;
use barrelstrength\sproutimport\models\Json;
use barrelstrength\sproutimport\queue\jobs\Import;
use barrelstrength\sproutimport\models\Seed;
use sproutimport\enums\ImportType;
use Craft;
use yii\console\ExitCode;

class SeedController extends Controller
{
    public $filePath;

    public function options($actionID)
    {
        return ['filePath'];
    }

    public function actionIndex()
    {
        if (!file_exists($this->filePath)) {
            $message = Craft::t("sprout-import", "File path does not exist.");
            $this->stdout($message);

            return ExitCode::DATAERR;
        }

        $fileContent = file_get_contents($this->filePath);

        if ($fileContent) {
            $seedModel = new Seed();
            $seedModel->seedType = ImportType::Console;
            $seedModel->enabled = 1;

            $importData = new Json();
            $importData->setJson($fileContent);

            // Make sure we have JSON
            if ($importData->hasErrors()) {
                $errorMessage = $importData->getFirstError('json');

                $this->stdout($errorMessage);

                return ExitCode::DATAERR;
            }

            $fileImportJob = new Import();
            $fileImportJob->seedAttributes = $seedModel->getAttributes();
            $fileImportJob->importData = $importData->json;

            Craft::$app->queue->push(new Import([
                'importData' => $fileImportJob->importData,
                'seedAttributes' => $fileImportJob->seedAttributes
            ]));
        }
    }
}