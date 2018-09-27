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

use barrelstrength\sproutbase\app\import\services\ElementImporter;
use barrelstrength\sproutbase\app\import\services\SettingsImporter;
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
     * @var ElementImporter
     */
    public $elementImporter;

    /**
     * @var SettingsImporter
     */
    public $settingsImporter;

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
        $this->faker = new Faker();
        $this->seed = new Seed();
        $this->fieldImporter = new FieldImporter();
        $this->elementImporter = new ElementImporter();
        $this->settingsImporter = new SettingsImporter();
    }
}
