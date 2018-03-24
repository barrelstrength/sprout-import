<?php

namespace barrelstrength\sproutimport\services;

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
use barrelstrength\sproutbase\contracts\sproutimport\BaseFieldImporter;
use barrelstrength\sproutbase\contracts\sproutimport\BaseImporter;
use barrelstrength\sproutimport\models\Seed as SeedModel;
use barrelstrength\sproutimport\models\Weed;
use barrelstrength\sproutimport\SproutImport;
use craft\base\Component;
use craft\base\Element;
use craft\events\RegisterComponentTypesEvent;
use Craft;

class Importers extends Component
{
    const EVENT_REGISTER_IMPORTER_TYPES = 'registerSproutImportImporters';

    /**
     * @var array
     */
    protected $importers = [];

    /**
     * @var array
     */
    protected $fieldImporters = [];

    /**
     * @var array
     */
    protected $seedImporters = [];

    /**
     * @return array
     */
    public function getSproutImportImporters(): array
    {
        $importerTypes = [

            // Elements
            Asset::class,
            Category::class,
            Entry::class,
            Tag::class,
            User::class,

            // Fields
            Assets::class,
            Categories::class,
            Checkboxes::class,
            Color::class,
            Date::class,
            Dropdown::class,
            Email::class,
            Entries::class,
            Lightswitch::class,
            Matrix::class,
            MultiSelect::class,
            Number::class,
            PlainText::class,
            RadioButtons::class,
            Table::class,
            Tags::class,
            Url::class,
            Users::class,

            // Settings
            Field::class,
            Section::class,
            Widget::class
        ];

        if (Craft::$app->getPlugins()->getPlugin('redactor')) {
            $importerTypes[] = Redactor::class;
        }

        $event = new RegisterComponentTypesEvent([
            'types' => $importerTypes
        ]);

        $this->trigger(self::EVENT_REGISTER_IMPORTER_TYPES, $event);

        $importers = $event->types;

        if (!empty($importers)) {
            foreach ($importers as $importerNamespace) {

                // Create an instance of our Importer object
                $importer = new $importerNamespace;

                // Pluck any Field Importers for their own list
                if ($importer && $importer instanceof BaseFieldImporter) {
                    $this->fieldImporters[$importerNamespace] = $importer;
                    continue;
                }

                if ($importer && $importer instanceof BaseImporter) {
                    $this->importers[$importerNamespace] = $importer;

                    if ($importer->hasSeedGenerator()) {
                        $this->seedImporters[$importerNamespace] = $importer;
                    }
                }
            }
        }

        uasort($this->importers, function($a, $b) {
            /**
             * @var $a BaseImporter
             * @var $b BaseImporter
             */
            return $a->getName() <=> $b->getName();
        });

        return $this->importers;
    }

    /**
     * @return array
     */
    public function getSproutImportSeedImporters()
    {
        if (count($this->seedImporters)) {
            return $this->seedImporters;
        }

        $this->getSproutImportImporters();

        return $this->seedImporters;
    }

    /**
     * @return array
     */
    public function getSproutImportFieldImporters()
    {
        // Make sure all of our Field Type classes are loaded

        if (count($this->fieldImporters)) {
            return $this->fieldImporters;
        }

        $this->getSproutImportImporters();

        return $this->fieldImporters;
    }

    /**
     * @param $namespace
     *
     * @return null
     */
    public function getFieldImporterClassByType($namespace)
    {
        $this->getSproutImportFieldImporters();

        $fieldClass = null;

        if ($this->fieldImporters !== null) {
            foreach ($this->fieldImporters as $importer) {
                if ($importer->getModelName() == $namespace) {
                    $fieldClass = $importer;
                }
            }
        }

        return $fieldClass;
    }

    /**
     * Get the Importer Model based on the "@model" key in the import data row
     * and return it if it exists.
     *
     * Examples:
     * - "@model": "barrelstrength\\sproutimport\\integrations\\sproutimport\\elements\\Entry"
     * - "@model": "barrelstrength\\sproutimport\\integrations\\sproutimport\\settings\\Field"
     *
     * @param      $settings
     *
     * @return bool
     */
    public function getImporter($settings)
    {
        // Make sure we have an @model attribute
        if (!isset($settings['@model'])) {
            $message = Craft::t('sprout-import', 'Importer class not found. Each item being imported requires an "@model" attribute.');

            $errorLog = [];
            $errorLog['message'] = $message;
            $errorLog['attributes'] = $settings;

            SproutImport::error($message);

            $this->addError('invalid-model-key', $errorLog);

            return false;
        }

        // Make sure the @model attribute class exists
        if (!class_exists($settings['@model'])) {

            $message = Craft::t('sprout-import', 'Class defined in @model attribute could not be found: {class}', [
                'class' => $settings['@model']
            ]);

            SproutImport::error($message);

            $this->addError('invalid-namespace', $message);

            return false;
        }

        $importerClass = $settings['@model'];

        // Remove our @model so only the actual settings exist in the $settings array
        unset($settings['@model']);

        // Instantiate our Importer's Class
        return new $importerClass($settings);
    }

    /**
     * @param           $importData
     * @param Weed|null $weedModel
     *
     * @return bool|\craft\base\Model|mixed|null
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function save($importData, Weed $weedModel = null)
    {
        $result = false;
        $importer = null;

        if ($importData === null) {
            return false;
        }

        // @todo - document which situations each condition below applies
        if (is_array($importData)) {
            // When seeding entries, we have an array
            $rows = $importData;
        } else {
            $rows = json_decode($importData, true);
        }

        foreach ($rows as $row) {
            /**
             * @var $importerClass BaseImporter
             */
            $importerClass = $this->getImporter($row);

            // Confirm model for this row of import data is supported
            if (!$importerClass) {
                return false;
            }

            if ($importerClass->model instanceof Element) {
                $importer = SproutImport::$app->elementImporter;

                $result = $importer->saveElement($row, $importerClass);
            } else {
                $importer = SproutImport::$app->settingsImporter;

                $result = $importer->saveSetting($row, $importerClass);
            }

            if ($weedModel != null) {
                if ($weedModel->seed == true && isset($result->id)) {
                    $seedAttributes = [
                        'itemId' => $result->id,
                        'importerClass' => get_class($importerClass),
                        'type' => $weedModel->type,
                        'details' => $weedModel->details,
                        'dateCreated' => $weedModel->dateSubmitted
                    ];

                    $seedModel = new SeedModel();

                    $seedModel->setAttributes($seedAttributes, false);

                    SproutImport::$app->seed->trackSeed($seedModel);
                }
            }
        }

        // Assign importer errors to utilities error for easy debugging and call of errors.
        if ($result == false && ($importer != null AND $importer->hasErrors())) {
            $this->addErrors($importer->getErrors());
        }

        return $result;
    }
}