<?php

namespace barrelstrength\sproutimport\migrations;

use craft\db\Migration;

/**
 * m180515_000000_rename_seedtype_column migration.
 */
class m180515_000000_rename_seedtype_column extends Migration
{
    /**
     * @inheritdoc
     *
     * @throws \yii\base\NotSupportedException
     */
    public function safeUp(): bool
    {
        $table = '{{%sproutimport_seeds}}';

        if (!$this->db->columnExists($table, 'seedType')) {
            $this->renameColumn($table, 'type', 'seedType');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m180515_000000_rename_seedtype_column cannot be reverted.\n";
        return false;
    }
}
