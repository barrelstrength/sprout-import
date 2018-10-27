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
            ],
            4 => [
                'oldType' => 'barrelstrength\sproutimport\integrations\sproutimport\settings\Section',
                'newType' => 'barrelstrength\sproutimport\importers\settings\Section'
            ],
            5 => [
                'oldType' => 'barrelstrength\sproutimport\integrations\sproutimport\settings\Field',
                'newType' => 'barrelstrength\sproutimport\importers\settings\Field'
            ],
            6 => [
                'oldType' => 'barrelstrength\sproutimport\integrations\sproutimport\settings\EntryType',
                'newType' => 'barrelstrength\sproutimport\importers\settings\EntryType'
            ],
            7 => [
                'oldType' => 'barrelstrength\sproutimport\integrations\sproutimport\settings\Widget',
                'newType' => 'barrelstrength\sproutimport\importers\settings\Widget'
            ]
        ];

        foreach ($seedClasses as $seedClass) {
            $this->update('{{%sproutimport_seeds}}', [
                'type' => $seedClass['newType']
            ], ['type' => $seedClass['oldType']], [], false);
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
