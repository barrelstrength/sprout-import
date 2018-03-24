<?php

namespace barrelstrength\sproutimport\queue\jobs;

use barrelstrength\sproutimport\SproutImport;
use craft\queue\BaseJob;
use Craft;

class Seed extends BaseJob
{
    /**
     * @var $seedJob
     */
    public $seedJob;

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        $seedJob = $this->seedJob;

        SproutImport::$app->seed->runSeed($seedJob);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('sprout-import', 'Seeding Data.');
    }
}
