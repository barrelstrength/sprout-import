<?php

namespace barrelstrength\sproutimport\migrations;

use barrelstrength\sproutbase\sproutimport\migrations\Install as SproutBaseImportInstall;
use craft\db\Migration;

class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->runSproutBaseInstall();

        return true;
    }

    protected function runSproutBaseInstall()
    {
        $migration = new SproutBaseImportInstall();

        ob_start();
        $migration->safeUp();
        ob_end_clean();
    }
}
