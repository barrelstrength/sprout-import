<?php

namespace barrelstrength\sproutimport\migrations;

use craft\db\Migration;

/**
 * m181027_000000_update_seed_types_to_sprout_base migration.
 */
class m181027_000000_update_seed_types_to_sprout_base extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $seedClasses = [
            0 => [
                'oldType' => 'barrelstrength\sproutimport\importers\elements\Category',
                'newType' => 'barrelstrength\sproutbase\app\import\importers\elements\Category'
            ],
            1 => [
                'oldType' => 'barrelstrength\sproutimport\importers\elements\Entry',
                'newType' => 'barrelstrength\sproutbase\app\import\importers\elements\Entry'
            ],
            2 => [
                'oldType' => 'barrelstrength\sproutimport\importers\elements\Tag',
                'newType' => 'barrelstrength\sproutbase\app\import\importers\elements\Tag'
            ],
            3 => [
                'oldType' => 'barrelstrength\sproutimport\importers\elements\User',
                'newType' => 'barrelstrength\sproutbase\app\import\importers\elements\User'
            ],
            4 => [
                'oldType' => 'barrelstrength\sproutimport\importers\settings\Section',
                'newType' => 'barrelstrength\sproutbase\app\import\importers\settings\Section'
            ],
            5 => [
                'oldType' => 'barrelstrength\sproutimport\importers\settings\Field',
                'newType' => 'barrelstrength\sproutbase\app\import\importers\settings\Field'
            ],
            6 => [
                'oldType' => 'barrelstrength\sproutimport\importers\settings\EntryType',
                'newType' => 'barrelstrength\sproutbase\app\import\importers\settings\EntryType'
            ],
            7 => [
                'oldType' => 'barrelstrength\sproutimport\importers\settings\Widget',
                'newType' => 'barrelstrength\sproutbase\app\import\importers\settings\Widget'
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
    public function safeDown(): bool
    {
        echo "m181027_000000_update_seed_types_to_sprout_base cannot be reverted.\n";
        return false;
    }
}
