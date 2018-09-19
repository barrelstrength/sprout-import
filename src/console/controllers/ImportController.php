<?php
namespace barrelstrength\sproutimport\console\controllers;

use yii\console\Controller;
use barrelstrength\sproutimport\models\Seed;
use sproutimport\enums\ImportType;
use barrelstrength\sproutimport\models\Json as JsonModel;
use Craft;
use yii\console\ExitCode;
use barrelstrength\sproutimport\queue\jobs\Import;

class ImportController extends Controller
{
    public $filePath;
    public $seed;

    public function options($actionID)
    {
        return ['filePath',  'seed'];
    }

    /**
     * Queue files for import
     */
    public function actionIndex()
    {
        if ($this->filePath) {
            $paths = array_map('trim', explode(' ', $this->filePath));
            if ($paths) {
                foreach ($paths as $path) {
                    $this->queueFile($path);

                    $message = Craft::t("sprout-import", $path . " file in queue.");
                    $this->stdout($message. PHP_EOL);
                }
            }
        }
    }

    private function queueFile($path)
    {
        if (!file_exists($path)) {
            $message = Craft::t("sprout-import", "File path does not exist.");
            $this->stdout($message);

            return ExitCode::DATAERR;
        }

        $fileContent = file_get_contents($path);

        if ($fileContent) {
            $importData = new JsonModel();
            $importData->setJson($fileContent);

            // Make sure we have JSON
            if ($importData->hasErrors()) {
                $errorMessage = $importData->getFirstError('json');

                $this->stdout($errorMessage);

                return ExitCode::DATAERR;
            }

            $seedModel = new Seed();
            $seedModel->seedType = ImportType::Console;
            $seedModel->enabled = ($this->seed)? true : false;

            $fileImportJob = new Import();
            $fileImportJob->seedAttributes = $seedModel->getAttributes();
            $fileImportJob->importData = $importData->json;

            Craft::$app->queue->push(new Import([
                'importData' => $fileImportJob->importData,
                'seedAttributes' => $fileImportJob->seedAttributes
            ]));
        }

        return null;
    }
}