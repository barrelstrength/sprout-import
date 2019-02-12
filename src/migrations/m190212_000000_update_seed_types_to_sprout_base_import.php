<?php

namespace barrelstrength\sproutimport\migrations;

use craft\db\Migration;

/**
 * m190212_000000_update_seed_types_to_sprout_base_import migration.
 */
class m190212_000000_update_seed_types_to_sprout_base_import extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $seedClasses = [
            0 => [
                'oldType' => 'barrelstrength\sproutbase\app\import\importers\elements\Category',
                'newType' => 'barrelstrength\sproutbaseimport\importers\elements\Category'
            ],
            1 => [
                'oldType' => 'barrelstrength\sproutbase\app\import\importers\elements\Entry',
                'newType' => 'barrelstrength\sproutbaseimport\importers\elements\Entry'
            ],
            2 => [
                'oldType' => 'barrelstrength\sproutbase\app\import\importers\elements\Tag',
                'newType' => 'barrelstrength\sproutbaseimport\importers\elements\Tag'
            ],
            3 => [
                'oldType' => 'barrelstrength\sproutbase\app\import\importers\elements\User',
                'newType' => 'barrelstrength\sproutbaseimport\importers\elements\User'
            ],
            4 => [
                'oldType' => 'barrelstrength\sproutbase\app\import\importers\settings\Section',
                'newType' => 'barrelstrength\sproutbaseimport\importers\settings\Section'
            ],
            5 => [
                'oldType' => 'barrelstrength\sproutbase\app\import\importers\settings\Field',
                'newType' => 'barrelstrength\sproutbaseimport\importers\settings\Field'
            ],
            6 => [
                'oldType' => 'barrelstrength\sproutbase\app\import\importers\settings\EntryType',
                'newType' => 'barrelstrength\sproutbaseimport\importers\settings\EntryType'
            ],
            7 => [
                'oldType' => 'barrelstrength\sproutbase\app\import\importers\settings\Widget',
                'newType' => 'barrelstrength\sproutbaseimport\importers\settings\Widget'
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
        echo "m190212_000000_update_seed_types_to_sprout_base_import cannot be reverted.\n";
        return false;
    }
}
