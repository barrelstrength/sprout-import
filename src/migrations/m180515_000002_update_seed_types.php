<?php

namespace barrelstrength\sproutimport\migrations;

use craft\db\Migration;

/**
 * m180515_000000_update_seed_types migration.
 */
class m180515_000002_update_seed_types extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $seedClasses = [
            0 => [
                'oldType' => 'barrelstrength\sproutimport\integrations\sproutimport\elements\Category',
                'newType' => 'barrelstrength\sproutimport\importers\elements\Category'
            ],
            1 => [
                'oldType' => 'barrelstrength\sproutimport\integrations\sproutimport\elements\Entry',
                'newType' => 'barrelstrength\sproutimport\importers\elements\Entry'
            ],
            2 => [
                'oldType' => 'barrelstrength\sproutimport\integrations\sproutimport\elements\Tag',
                'newType' => 'barrelstrength\sproutimport\importers\elements\Tag'
            ],
            3 => [
                'oldType' => 'barrelstrength\sproutimport\integrations\sproutimport\elements\User',
                'newType' => 'barrelstrength\sproutimport\importers\elements\User'
            ]
        ];

        foreach ($seedClasses as $seedClass) {
            $this->update('{{%sproutimport_seeds}}', [
                'type' => $seedClass['newType']], ['type' => $seedClass['oldType']], [], false);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180515_000000_update_seed_types cannot be reverted.\n";
        return false;
    }
}
