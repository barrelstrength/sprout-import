<?php
namespace barrelstrength\sproutimport\integrations\sproutimport\elements;
use barrelstrength\sproutbase\contracts\sproutimport\BaseElementImporter;
use craft\commerce\elements\Order as OrderElement;
use Craft;
use craft\commerce\Plugin;

class Order extends BaseElementImporter
{
	/** @var string Session key for storing the cart number */
	protected $cookieCartId = 'commerce_cookie';

	/**
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('sprout-import', 'Commerce Orders');
	}

	/**
	 * @return mixed
	 */
	public function getModelName()
	{
		return OrderElement::class;
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

		$this->model->setAttributes($settings['attributes']);

		if ($settings['lineItems']) {
            $this->model->setLineItems($settings['lineItems']);
        }

		$this->model->isCompleted = (!empty($settings['isCompleted'])) ? $settings['isCompleted'] : 0;

		if (empty($this->model->number))
		{
			$this->model->number = $this->getRandomCartNumber();
		}

		$this->model->paymentCurrency = Plugin::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrencyIso();

		//Bypass validation
		$this->model->customerId = 0;
	}

    /**
     * @param $id
     *
     * @return bool
     * @throws \Throwable
     */
	public function deleteById($id)
	{
		return Craft::$app->elements->deleteElementById($id);
	}

	public function getImporterDataKeys()
	{
		return array('lineItems', 'transactions', 'addresses');
	}

	/**
	 * This is code is from Commerce_CartService copied it because it is private
	 *
	 * @return mixed|string
	 */
	private function getRandomCartNumber()
	{
		$number = md5(uniqid(mt_rand(), true));

		$order = Plugin::getInstance()->getOrders()->getOrderByNumber($number);
		// Make sure not duplicate number
		if ($order)
		{
			return $this->getRandomCartNumber();
		}

		return $number;
	}

	public function getFieldLayoutId($model)
    {
        // TODO: Implement getFieldLayoutId() method.
    }
}