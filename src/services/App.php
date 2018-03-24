<?php
/**
 * Sprout Import plugin for Craft CMS 3.x
 *
 * Import content and settings. Generate fake data.
 *
 * @link      http://barrelstrengthdesign.com
 * @copyright Copyright (c) 2017 Barrel Strength Design
 */

namespace barrelstrength\sproutimport\services;

use craft\base\Component;

/**
 * Class App
 */
class App extends Component
{
    /**
     * @var Utilities
     */
    public $utilities;

    /**
     * @var Faker
     */
    public $faker;

    /**
     * @var Importers
     */
    public $importers;

    /**
     * @var ElementImporter
     */
    public $elementImporter;

    /**
     * @var SettingsImporter
     */
    public $settingsImporter;

    /**
     * @var Themes
     */
    public $themes;

    /**
     * @var FieldImporter
     */
    public $fieldImporter;

    /**
     * @var Seed
     */
    public $seed;

    public function init()
    {
        $this->utilities = Utilities::Instance();
        $this->faker = new Faker();
        $this->seed = new Seed();
        $this->fieldImporter = new FieldImporter();
        $this->importers = new Importers();
        $this->elementImporter = new ElementImporter();
        $this->settingsImporter = new SettingsImporter();
        $this->themes = new Themes();
    }
}
