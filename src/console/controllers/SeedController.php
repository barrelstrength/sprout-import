<?php

namespace barrelstrength\sproutimport\console\controllers;

use barrelstrength\sproutbaseimport\base\Importer;
use barrelstrength\sproutbaseimport\SproutBaseImport;
use craft\helpers\DateTimeHelper;
use yii\console\Controller;
use Craft;
use yii\console\ExitCode;
use barrelstrength\sproutbaseimport\models\jobs\SeedJob;
use barrelstrength\sproutbaseimport\enums\ImportType;

class SeedController extends Controller
{
    /**
     * @var string The Element Class for which you wish to generate seeds
     *
     * @example craft\elements\Entry
     */
    public $element;

    /**
     * @var array Any settings necessary for the seed job
     *
     * @example
     * A settings array can be sent as a string using a comma delimiter
     * and a key=value separated with an equals sign
     * --settings="value1,value2"
     * --settings="section=news;entryType=post"
     */
    public $settings = [];

    /**
     * @var integer The number of items you would like to seed to your database
     */
    public $quantity = 11;

    /**
     * @inheritdoc
     */
    public $defaultAction = 'generate';

    /**
     * @param string $actionID
     *
     * @return array|string[]
     */
    public function options($actionID): array
    {
        return ['element', 'settings', 'quantity'];
    }

    /**
     * @inheritdoc
     */
    public function optionAliases(): array
    {
        $aliases = parent::optionAliases();
        $aliases['e'] = 'element';
        $aliases['s'] = 'settings';
        $aliases['q'] = 'quantity';

        return $aliases;
    }

    /**
     * Seed your database with dummy content
     */
    public function actionGenerate()
    {
        if (!$this->element) {
            $message = Craft::t('sprout-import', 'Invalid attribute: --element requires an Element class');
            $this->stdout($message);

            return ExitCode::DATAERR;
        }

        /** @var Importer[] $allSeedImporters */
        $allSeedImporters = SproutBaseImport::$app->importers->getSproutImportSeedImporters();

        foreach ($allSeedImporters as $seedImporter) {
            // Allow the command to use the actual element class
            // or the Element Importer class
            if ($seedImporter->getModelName() === $this->element OR
                get_class($seedImporter) === $this->element) {
                $this->element = get_class($seedImporter);
                continue;
            }
        }

        if ($this->settings) {
            $seedSettings = [];
            foreach ($this->settings as $key => $value) {
                if (false !== strpos($value, '=')) {
                    $value = explode('=', $value);
                }

                // If we have a setting with a key/value pair
                if (isset($value[0], $value[1]) && is_array($value)) {
                    $seedSettings[$value[0]] = $value[1];
                } else {
                    $seedSettings[] = $value;
                }
            }

            if (is_array($seedSettings)) {
                $this->settings = $seedSettings;
            } else {
                $this->settings = [$seedSettings];
            }
        }

        $details = Craft::t('sprout-import', '{elementType} Element(s)', [
            'elementType' => $this->element
        ]);

        $seedJob = new SeedJob();
        $seedJob->elementType = $this->element;
        $seedJob->quantity = $this->quantity;
        $seedJob->settings = $this->settings;
        $seedJob->seedType = ImportType::Seed;
        $seedJob->details = $details;
        $seedJob->dateCreated = DateTimeHelper::currentUTCDateTime();

        $seedJobErrors = null;

        if (SproutBaseImport::$app->seed->generateSeeds($seedJob)) {
            $message = Craft::t('sprout-import', $this->element.' seed in queue.');
            $this->stdout($message.PHP_EOL);
        }

        // @todo - doesn't behave as expected, just removes the job from the db
        // Craft::$app->getQueue()->run();

        return null;
    }
}