<?php

namespace barrelstrength\sproutimport\events;

use yii\base\Event;

class ElementImportEvent extends Event
{
    /**
     * @var $modelName
     */
    public $modelName;

    /**
     * @var $element
     */
    public $element;

    /**
     * @var $seed
     */
    public $seed;
}