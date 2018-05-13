<?php

namespace barrelstrength\sproutimport\importers\elements;
use barrelstrength\sproutbase\app\import\base\ElementImporter;
use craft\commerce\elements\Product as ProductElement;
use craft\commerce\elements\Variant;
use craft\commerce\records\Purchasable;

class Product extends ElementImporter
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

        $this->model->setVariants($rowVariants);
    }

    public function getFieldLayoutId($model)
    {
        // TODO: Implement getFieldLayoutId() method.
    }
}