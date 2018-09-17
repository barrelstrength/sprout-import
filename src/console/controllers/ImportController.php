<?php
namespace barrelstrength\sproutimport\console\controllers;

use barrelstrength\sproutimport\SproutImport;
use yii\console\Controller;
use barrelstrength\sproutimport\models\Json;
use barrelstrength\sproutimport\queue\jobs\Import;
use barrelstrength\sproutimport\models\Seed;
use sproutimport\enums\ImportType;
use craft\helpers\FileHelper;
use Craft;

class ImportController extends Controller
{
    public $filePath;

    public function options($actionID)
    {
        return ['filePath'];
    }

    public function actionTest()
    {
        $fileContent = file_get_contents($this->filePath);

        if ($fileContent) {
            $seedModel = new Seed();
            $seedModel->seedType = ImportType::File;
            $seedModel->enabled = 1;

            $importData = new Json();
            $importData->setJson($fileContent);


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