<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\elements;

use Craft;
use barrelstrength\sproutbase\contracts\sproutimport\BaseElementImporter;
use craft\elements\Asset as AssetElement;

class Asset extends BaseElementImporter
{
    public function getName()
    {
        return Craft::t('sprout-import', 'Assets');
    }

    /**
     * @return mixed
     */
    public function getModelName()
    {
        return AssetElement::class;
    }

    public function getFieldLayoutId($model)
    {

    }
}