<?php
/**
 * Sprout Import plugin for Craft CMS 3.x
 *
 * Import content and settings. Generate fake data.
 *
 * @link      http://barrelstrengthdesign.com
 * @copyright Copyright (c) 2017 Barrel Strength Design
 */

namespace barrelstrength\sproutimport;

use barrelstrength\sproutbase\base\BaseSproutTrait;
use barrelstrength\sproutbase\SproutBaseHelper;
use barrelstrength\sproutbaseimport\SproutBaseImportHelper;
use barrelstrength\sproutbaseimport\models\Settings;
use Craft;
use craft\base\Plugin;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use yii\base\Event;

/**
 * Class SproutImport
 *
 * @package barrelstrength\sproutimport
 *
 * @property array $userPermissions
 * @property array $cpUrlRules
 * @property array $cpNavItem
 */
class SproutImport extends Plugin
{
    use BaseSproutTrait;

    /**
     * Identify our plugin for BaseSproutTrait
     *
     * @var string
     */
    public static $pluginHandle = 'sprout-import';

    /**
     * @var bool
     */
    public $hasCpSection = true;

    /**
     * @var string
     */
    public $schemaVersion = '1.0.3';

    /**
     * @var string
     */
    public $minVersionRequired = '0.6.3';

    const EDITION_LITE = 'lite';
    const EDITION_PRO = 'pro';

    /**
     * @inheritdoc
     */
    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            self::EDITION_PRO,
        ];
    }

    public function init()
    {
        parent::init();

        SproutBaseHelper::registerModule();
        SproutBaseImportHelper::registerModule();

        Craft::setAlias('@sproutimport', $this->getBasePath());

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, $this->getCpUrlRules());
        });

        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $event->permissions['Sprout Import'] = $this->getUserPermissions();
        });
    }

    /**
     * @return Settings
     */
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    /**
     * @return array
     */
    public function getCpNavItem(): array
    {
        $parent = parent::getCpNavItem();

        // Allow user to override plugin name in sidebar
        if ($this->getSettings()->pluginNameOverride) {
            $parent['label'] = $this->getSettings()->pluginNameOverride;
        }

        return array_merge($parent, [
            'subnav' => [
                'index' => [
                    'label' => Craft::t('sprout-import', 'Import'),
                    'url' => 'sprout-import/index'
                ],
                'seed' => [
                    'label' => Craft::t('sprout-import', 'Seed'),
                    'url' => 'sprout-import/seed'
                ],
                'weed' => [
                    'label' => Craft::t('sprout-import', 'Weed'),
                    'url' => 'sprout-import/weed'
                ],
//                'bundles' => [
//                    'label' => Craft::t('sprout-import', 'Bundles'),
//                    'url' => 'sprout-import/bundles'
//                ],
                'settings' => [
                    'label' => Craft::t('sprout-import', 'Settings'),
                    'url' => 'sprout-import/settings/general'
                ]
            ]
        ]);
    }

    private function getCpUrlRules(): array
    {
        return [
            'sprout-import' => [
                'template' => 'sprout-base-import/index'
            ],
            'sprout-import/index' => [
                'template' => 'sprout-base-import/index'
            ],
            'sprout-import/seed' =>
                'sprout-base-import/seed/seed-index',
            'sprout-import/weed' =>
                'sprout-base-import/weed/weed-index',
            'sprout-import/bundles' => [
                'template' => 'sprout-base-import/bundles'
            ],
            'sprout-import/settings' =>
                'sprout/settings/edit-settings',
            'sprout-import/settings/<settingsSectionHandle:.*>' =>
                'sprout/settings/edit-settings'
        ];
    }

    /**
     * @return array
     */
    public function getUserPermissions(): array
    {
        return [
            'sproutImport-generateSeeds' => [
                'label' => Craft::t('sprout-import', 'Generate Seed data'),
                'nested' => [
                    'sproutImport-removeSeeds' => [
                        'label' => Craft::t('sprout-import', 'Remove Seed data')
                    ]
                ]
            ],
//            'sproutImport-importBundles' => [
//                'label' => Craft::t('sprout-import', 'Import Bundles')
//            ],
        ];
    }
}
