<?php

namespace barrelstrength\sproutimport\models\jobs;

use craft\base\Model;
use sproutimport\enums\ImportType;
use barrelstrength\sproutbase\contracts\sproutimport\BaseElementImporter;

class SeedJob extends Model
{
    /**
     * The Element Type for which to generate Seeds
     *
     * @var string
     */
    public $elementType;

    /**
     * The Import Type
     *
     * @var ImportType $type
     */
    public $type;

    /**
     * The number of seeds that will be generated
     *
     * @var int
     */
    public $quantity;

    /**
     * Additional settings the Element Importer will use
     *
     * @see BaseElementImporter
     *
     * @var string
     */
    public $settings;

    /**
     * @var string
     */
    public $details;

    /**
     * @var \DateTime
     */
    public $dateCreated;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['elementType'], 'required'],
            [['settings'], 'validateSeedSettings']
        ];
    }

    /**
     * Allow Element Importers to validate Seed settings before generating Seeds
     */
    public function validateSeedSettings()
    {
        /**
         * @var $elementImporter BaseElementImporter
         */
        $elementImporter = new $this->elementType;

        if ($errors = $elementImporter->getSeedSettingsErrors($this->settings))
        {
            $this->addError('settings', $errors);
        }
    }
}