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
use barrelstrength\sproutimport\integrations\sproutimport\elements\Asset;
use barrelstrength\sproutimport\integrations\sproutimport\elements\Category;
use barrelstrength\sproutimport\integrations\sproutimport\elements\Entry;
use barrelstrength\sproutimport\integrations\sproutimport\elements\Tag;
use barrelstrength\sproutimport\integrations\sproutimport\elements\User;
use barrelstrength\sproutimport\integrations\sproutimport\fields\Assets;
use barrelstrength\sproutimport\integrations\sproutimport\fields\Categories;
use barrelstrength\sproutimport\integrations\sproutimport\fields\Checkboxes;
use barrelstrength\sproutimport\integrations\sproutimport\fields\Color;
use barrelstrength\sproutimport\integrations\sproutimport\fields\Date;
use barrelstrength\sproutimport\integrations\sproutimport\fields\Dropdown;
use barrelstrength\sproutimport\integrations\sproutimport\fields\Entries;
use barrelstrength\sproutimport\integrations\sproutimport\fields\Lightswitch;
use barrelstrength\sproutimport\integrations\sproutimport\fields\Matrix;
use barrelstrength\sproutimport\integrations\sproutimport\fields\MultiSelect;
use barrelstrength\sproutimport\integrations\sproutimport\fields\Number;
use barrelstrength\sproutimport\integrations\sproutimport\fields\Email;
use barrelstrength\sproutimport\integrations\sproutimport\fields\PlainText;
use barrelstrength\sproutimport\integrations\sproutimport\fields\RadioButtons;
use barrelstrength\sproutimport\integrations\sproutimport\fields\Redactor;
use barrelstrength\sproutimport\integrations\sproutimport\fields\Table;
use barrelstrength\sproutimport\integrations\sproutimport\fields\Tags;
use barrelstrength\sproutimport\integrations\sproutimport\fields\Url;
use barrelstrength\sproutimport\integrations\sproutimport\fields\Users;
use barrelstrength\sproutimport\integrations\sproutimport\settings\Field;
use barrelstrength\sproutimport\integrations\sproutimport\settings\Section;
use barrelstrength\sproutimport\integrations\sproutimport\settings\Widget;
use barrelstrength\sproutimport\integrations\sproutimport\themes\SimpleTheme;
use barrelstrength\sproutimport\models\Settings;
use barrelstrength\sproutimport\services\App;
use barrelstrength\sproutimport\services\Importers;
use barrelstrength\sproutimport\services\Themes;
use barrelstrength\sproutbase\helpers\UninstallHelper;
use Craft;
use craft\base\Plugin;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use yii\base\Event;
use craft\events\RegisterComponentTypesEvent;
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

    public $hasCpSection = true;

    public function init()
    {
        parent::init();

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
            $event->rules['sprout-import/settings'] = 'sprout-base/settings/edit-settings';
            $event->rules['sprout-import/settings/<settingsSectionHandle:.*>'] = 'sprout-base/settings/edit-settings';
        });

        Event::on(Importers::class, Importers::EVENT_REGISTER_IMPORTER, function(RegisterComponentTypesEvent $event) {
            $event->types[] = Asset::class;
            $event->types[] = Category::class;
            $event->types[] = Entry::class;
            $event->types[] = Tag::class;
            $event->types[] = User::class;

            $event->types[] = Assets::class;
            $event->types[] = Categories::class;
            $event->types[] = Checkboxes::class;
            $event->types[] = Color::class;
            $event->types[] = Date::class;
            $event->types[] = Dropdown::class;
            $event->types[] = Email::class;
            $event->types[] = Entries::class;
            $event->types[] = Lightswitch::class;
            $event->types[] = Matrix::class;
            $event->types[] = MultiSelect::class;
            $event->types[] = Number::class;
            $event->types[] = PlainText::class;
            $event->types[] = RadioButtons::class;
            $event->types[] = Table::class;
            $event->types[] = Tags::class;
            $event->types[] = Url::class;
            $event->types[] = Users::class;

            $event->types[] = Field::class;
            $event->types[] = Section::class;
            $event->types[] = Widget::class;

            if (Craft::$app->getPlugins()->getPlugin('redactor')) {
                $event->types[] = Redactor::class;
            }
        });

        Event::on(Themes::class, Themes::EVENT_REGISTER_THEMES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = SimpleTheme::class;
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
                'themes' => [
                    'label' => Craft::t('sprout-import', 'Themes'),
                    'url' => 'sprout-import/themes'
                ],
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
