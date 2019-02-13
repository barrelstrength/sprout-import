<?php

namespace barrelstrength\sproutimport\migrations;

use barrelstrength\sproutbaseimport\migrations\m190212_000000_update_seed_types_to_sprout_base_import;
use craft\db\Migration;

/**
 * m190212_000000_update_seed_types_to_sprout_base_import_sproutimport migration.
 */
class m190212_000000_update_seed_types_to_sprout_base_import_sproutimport extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $migration = new m190212_000000_update_seed_types_to_sprout_base_import();

        ob_start();
        $migration->safeUp();
        ob_end_clean();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m190212_000000_update_seed_types_to_sprout_base_import_sproutimport cannot be reverted.\n";
        return false;
    }
}
