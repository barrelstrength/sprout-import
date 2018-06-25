<?php

namespace barrelstrength\sproutimport\models;

use craft\base\Model;
use sproutimport\enums\ImportType;

class Weed extends Model
{
    /**
     * Whether a Weed model should be tracked in the db or not
     *
     * @var $seed
     */
    public $seed;

    /**
     * Method used to import the content that generated this specific Seed record
     *
     * @var ImportType $seedType
     */
    public $seedType;

    /**
     * Message describing the type of Seed that was created
     *
     * @var $details
     */
    public $details;

    /**
     * The date an item was created via Sprout Import
     *
     * @var $dateSubmitted
     */
    public $dateSubmitted;

}