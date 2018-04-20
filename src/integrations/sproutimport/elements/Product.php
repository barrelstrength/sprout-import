<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\elements;
use barrelstrength\sproutbase\contracts\sproutimport\BaseElementImporter;
use craft\commerce\elements\Product as ProductElement;

class Product extends BaseElementImporter
{
    public function getModelName()
    {
        return ProductElement::class;
    }
    /**
     * @return array
     */
    public function getImporterDataKeys()
    {
        return ['variants'];
    }

    /**
     * @param       $model
     * @param array $settings
     *
     * @return bool|mixed|void
     * @throws \Exception
     */
    public function setModel($model, array $settings = [])
    {
        $this->model = parent::setModel($model, $settings);

        $this->model->setVariants($settings['variants']);
    }

    public function getFieldLayoutId($model)
    {
        // TODO: Implement getFieldLayoutId() method.
    }
}