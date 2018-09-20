<?php
namespace barrelstrength\sproutimport\console\controllers;

use barrelstrength\sproutimport\SproutImport;
use craft\helpers\DateTimeHelper;
use yii\console\Controller;
use craft\helpers\Json;
use Craft;
use yii\console\ExitCode;
use barrelstrength\sproutimport\models\jobs\SeedJob;
use sproutimport\enums\ImportType;

class SeedController extends Controller
{
    public $content;
    public $settingPath;
    public $quantity;

    public function options($actionID)
    {
        return ['content', 'quantity', 'settingPath'];
    }

    public function actionIndex()
    {
        if (!file_exists($this->settingPath)) {
            $message = Craft::t("sprout-import", "File path does not exist.");
            $this->stdout($message);

            return ExitCode::DATAERR;
        }

        $jsonSetting = file_get_contents($this->settingPath);

        $setting = Json::decode($jsonSetting);

        $weedMessage = Craft::t('sprout-import', '{elementType} Element(s)');

        $details = Craft::t('sprout-import', $weedMessage, [
            'elementType' => $this->content
        ]);

        $seedJob = new SeedJob();
        $seedJob->elementType = $this->content;
        $seedJob->quantity = !empty($this->quantity) ? $this->quantity : 11;
        $seedJob->settings = $setting;
        $seedJob->seedType = ImportType::Seed;
        $seedJob->details = $details;
        $seedJob->dateCreated = DateTimeHelper::currentUTCDateTime();

        $seedJobErrors = null;

        if (SproutImport::$app->seed->generateSeeds($seedJob)) {
            $message = Craft::t("sprout-import", $this->content . " seed in queue.");
            $this->stdout($message. PHP_EOL);
        }

        return null;
    }
}