<?php

namespace barrelstrength\sproutimport\models;

use barrelstrength\sproutbase\base\SproutSettingsInterface;
use Craft;
use craft\base\Model;

/**
 *
 * @property array $settingsNavItems
 */
class Settings extends Model implements SproutSettingsInterface
{
    /**
     * @var string
     */
    public $pluginNameOverride = '';

    /**
     * @var bool
     */
    public $appendTitleValue = false;

    /**
     * @var string
     */
    public $localeIdOverride = '';

    /**
     * @var bool
     */
    public $displayFieldHandles = false;

    /**
     * @var bool
     */
    public $enableCustomSections = false;

    /**
     * @var bool
     */
    public $enableMetaDetailsFields = false;

    /**
     * @var bool
     */
    public $enableMetadataRendering = true;

    /**
     * @var string
     */
    public $metadataVariable = 'metadata';

    /**
     * @var array
     */
    public $seedSettings = [];

    /**
     * @inheritdoc
     */
    public function getSettingsNavItems(): array
    {
        return [
            'settingsHeading' => [
                'heading' => Craft::t('sprout-import', 'Settings'),
            ],
            'general' => [
                'label' => Craft::t('sprout-import', 'General'),
                'url' => 'sprout-import/settings/general',
                'selected' => 'general',
                'template' => 'sprout-base-import/settings/general'
            ],
            'seed' => [
                'label' => Craft::t('sprout-import', 'Seed Defaults'),
                'url' => 'sprout-import/settings/seed',
                'selected' => 'seed',
                'template' => 'sprout-base-import/settings/seed-defaults/index'
            ],
            'integrationsHeading' => [
                'heading' => Craft::t('sprout-import', 'Integrations'),
            ],
            'sproutseo' => [
                'label' => Craft::t('sprout-import', 'SEO'),
                'url' => 'sprout-import/settings/sproutseo',
                'selected' => 'sproutseo',
                'template' => 'sprout-base-import/settings/seo',
                'settingsForm' => false
            ],
        ];
    }
}