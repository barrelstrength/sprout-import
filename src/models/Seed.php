<?php

namespace barrelstrength\sproutimport\models;

use craft\base\Model;
use craft\helpers\DateTimeHelper;

class Seed extends Model
{
    public $itemId;

    public $importerClass;

    public $type;

    public $details = '';

    public $items;

    public $enabled = false;

    public $dateCreated;

    public $dateUpdated;

    public function __construct()
    {
        parent::__construct();

        $currentDate = DateTimeHelper::currentUTCDateTime();
        $this->dateCreated = $currentDate->format('Y-m-d H:i:s');
    }
}