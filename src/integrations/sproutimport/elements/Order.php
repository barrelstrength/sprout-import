<?php
namespace barrelstrength\sproutimport\integrations\sproutimport\elements;
use barrelstrength\sproutbase\contracts\sproutimport\BaseElementImporter;
use craft\commerce\elements\Order as OrderElement;
use Craft;
use craft\commerce\errors\PaymentException;
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

        $this->model = Plugin::getInstance()->getCarts()->getCart();

		$this->model->setAttributes($settings['attributes'], false);

		if ($settings['lineItems']) {
		    foreach ($settings['lineItems'] as $item) {
                $lineItem = Plugin::getInstance()->getLineItems()
                    ->resolveLineItem($this->model->id, $item['purchasableId'], $item['options'], $item['qty'], '');
                $this->model->addLineItem($lineItem);
            }
        }

		$this->model->isCompleted = $settings['attributes']['isCompleted'] ?: 0;

        $orderStatus = Plugin::getInstance()->getOrderStatuses()->getDefaultOrderStatusForOrder($this->model);
		if ($orderStatus) {
            $this->model->orderStatusId = $orderStatus->id;
        }

		$this->model->paymentCurrency = Plugin::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrencyIso();
	}

    /**
     * @return bool|void
     * @throws \Throwable
     */
	public function save()
    {
        parent::save();

        $settings = $this->rows;
        $order = $this->model;
        if ($settings['payments']) {
            $gateway = $order->getGateway();

            if (!$gateway) {
                $error = Craft::t('sprout-import', 'There is no gateway selected for this order.');
            }

            // Get the gateway's payment form
            $paymentForm = $gateway->getPaymentFormModel();

            $paymentForm->setAttributes($settings['payments'], false);

            $redirect = '';
            $transaction = null;
            if (!$paymentForm->hasErrors() && !$order->hasErrors()) {
                try {
                    Plugin::getInstance()->getPayments()->processPayment($order, $paymentForm, $redirect, $transaction);
                    $success = true;
                } catch (PaymentException $exception) {
                    $customError = $exception->getMessage();
                    $success = false;
                }
            } else {
                $customError = Craft::t('sprout-import', 'Invalid payment or order. Please review.');
                $success = false;
            }
        }
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
		return array('lineItems', 'payments', 'transactions', 'addresses');
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