<?php

namespace barrelstrength\sproutimport\importers\elements;
use barrelstrength\sproutbase\app\import\base\ElementImporter;
use barrelstrength\sproutimport\SproutImport;
use craft\commerce\elements\Product as ProductElement;
use Craft;
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
     * @param \yii\base\Model $model
     * @param array           $settings
     *
     * @return bool|mixed|void
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function setModel($model, array $settings = [])
    {
        $this->model = parent::setModel($model, $settings);

        $variants = $settings['variants'] ?? null;
        $rowVariants = [];
        if ($variants) {
            foreach ($variants as $key => $variant) {

                $var = Purchasable::find()->where(['sku' => $variant['sku']])->one();
                if ($var) {
                    $rowVariants[$var->id] = $variant;

                    if (!$this->model->id) {
                        SproutImport::$app
                            ->utilities
                            ->addError('exist-' . $variant['sku'], $variant['sku'] . ' sku already exists');
                    }
                } else {
                    $rowVariants["new" . $key] = $variant;
                }
            }
        }

        /**
         * @var $product ProductElement
         */
        $product = $this->model;

        if ($this->model !== null && count($rowVariants)) {
            $product->setVariants($rowVariants);
        }
    }

    public function getFieldLayoutId($model)
    {
        // TODO: Implement getFieldLayoutId() method.
    }

}