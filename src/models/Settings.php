<?php

namespace barrelstrength\sproutimport\models;

use Craft;
use craft\base\Model;

class Settings extends Model
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
     * @return array
     */
    public function getSettingsNavItems()
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