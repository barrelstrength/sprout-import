<?php

namespace barrelstrength\sproutimport\migrations;

use craft\db\Migration;

/**
 * m180515_000001_rename_importClass_column migration.
 */
class m180515_000001_rename_importClass_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = '{{%sproutimport_seeds}}';

        if ($this->db->columnExists($table, 'importClass')) {
            $this->renameColumn($table, 'importClass', 'type');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180515_000001_rename_importClass_column cannot be reverted.\n";
        return false;
    }
}
