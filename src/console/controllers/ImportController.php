<?php
namespace barrelstrength\sproutimport\console\controllers;

use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutimport\models\Weed;
use craft\helpers\DateTimeHelper;
use yii\console\Controller;
use craft\helpers\Json;
use barrelstrength\sproutimport\queue\jobs\Import;
use barrelstrength\sproutimport\models\Seed;
use sproutimport\enums\ImportType;
use barrelstrength\sproutimport\models\Json as JsonModel;
use Craft;
use yii\console\ExitCode;

class ImportController extends Controller
{
    public $filePath;
    public $seed;

    public function options($actionID)
    {
        return ['filePath',  'seed'];
    }

    /**
     * @return int
     * @throws \ReflectionException
     */
    public function actionIndex()
    {
        if (!file_exists($this->filePath)) {
            $message = Craft::t("sprout-import", "File path does not exist.");
            $this->stdout($message);

            return ExitCode::DATAERR;
        }

        $fileContent = file_get_contents($this->filePath);

        if ($fileContent) {

            $importData = new JsonModel();
            $importData->setJson($fileContent);

            // Make sure we have JSON
            if ($importData->hasErrors()) {
                $errorMessage = $importData->getFirstError('json');

                $this->stdout($errorMessage);

                return ExitCode::DATAERR;
            }

            $currentDate = DateTimeHelper::currentUTCDateTime();
            $dateCreated = $currentDate->format('Y-m-d H:i:s');

            $weedModelAttributes = [
                'seed' => ($this->seed)? true : false,
                'seedType' => ImportType::Console,
                'details' => Craft::t('sprout-import', 'Import Type: '.ImportType::Console),
                'dateSubmitted' => $dateCreated
            ];

            $weedModel = new Weed();
            $weedModel->setAttributes($weedModelAttributes, false);

            $importData = Json::decode($importData->json, true);

            SproutBase::$app->importers->save($importData, $weedModel);
        }
    }
}