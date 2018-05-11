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
use barrelstrength\sproutimport\models\Settings;
use barrelstrength\sproutimport\services\App;
use barrelstrength\sproutbase\helpers\UninstallHelper;
use Craft;
use craft\base\Plugin;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use yii\base\Event;
use barrelstrength\sproutimport\web\twig\variables\SproutImportVariable;

/**
 * Class SproutImport
 *
 * @package barrelstrength\sproutimport
 */
class SproutImport extends Plugin
{
    use BaseSproutTrait;

    /**
     * Enable use of SproutImport::$app-> in place of Craft::$app->
     *
     * @var \barrelstrength\sproutimport\services\App
     */
    public static $app;

    /**
     * Identify our plugin for BaseSproutTrait
     *
     * @var string
     */
    public static $pluginId = 'sprout-import';

    /**
     * @var bool
     */
    public $hasCpSection = true;

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    public function init()
    {
        parent::init();

        SproutBaseHelper::registerModule();

        $this->setComponents([
            'app' => App::class
        ]);

        self::$app = $this->get('app');

        Craft::setAlias('@sproutimport', $this->getBasePath());

        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $variable = $event->sender;
            $variable->set('sproutImport', SproutImportVariable::class);
        });

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['sprout-import/index'] = ['template' => 'sprout-import/index'];
            $event->rules['sprout-import/weed'] = 'sprout-import/weed/weed-index';
            $event->rules['sprout-import/seed'] = 'sprout-import/seed/seed-index';
            $event->rules['sprout-import/settings'] = 'sprout-base/sprout-base-settings/edit-settings';
            $event->rules['sprout-import/settings/<settingsSectionHandle:.*>'] = 'sprout-base/sprout-base-settings/edit-settings';
        });
    }

    /**
     * @return Settings
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @return array
     */
    public function getCpNavItem()
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
//                'themes' => [
//                    'label' => Craft::t('sprout-import', 'Themes'),
//                    'url' => 'sprout-import/themes'
//                ],
                'settings' => [
                    'label' => Craft::t('sprout-import', 'Settings'),
                    'url' => 'sprout-import/settings/general'
                ]
            ]
        ]);
    }

    /**
     * Uninstall Sprout Import and related schema
     */
    public function uninstall()
    {
        $uninstallHelper = new UninstallHelper($this);
        $uninstallHelper->uninstall();
    }
}
