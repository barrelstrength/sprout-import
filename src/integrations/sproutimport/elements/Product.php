<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\elements;
use barrelstrength\sproutbase\contracts\sproutimport\BaseElementImporter;
use craft\commerce\elements\Product as ProductElement;
use craft\commerce\elements\Variant;
use craft\commerce\records\Purchasable;

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

        $variants = $settings['variants'];
        $rowVariants = [];
        if ($variants) {
            foreach ($variants as $key => $variant) {

                $var = Purchasable::find()->where(['sku' => $variant['sku']])->one();
                if ($var) {
                    $rowVariants[$var->id] = $variant;
                } else {
                    $rowVariants["new" . $key] = $variant;
                }

            }
        }
        //\Craft::dd($rowVariants);
        $this->model->setVariants($rowVariants);
    }

    public function getFieldLayoutId($model)
    {
        // TODO: Implement getFieldLayoutId() method.
    }
}