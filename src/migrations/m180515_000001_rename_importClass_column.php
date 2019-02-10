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
     *
     * @throws \yii\base\NotSupportedException
     */
    public function safeUp(): bool
    {
        $table = '{{%sproutimport_seeds}}';

        if ($this->db->columnExists($table, 'importerClass')) {
            $this->renameColumn($table, 'importerClass', 'type');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m180515_000001_rename_importClass_column cannot be reverted.\n";
        return false;
    }
}
