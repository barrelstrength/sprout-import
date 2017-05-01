<?php
namespace Craft;

class Commerce_OrderSproutImportElementImporter extends BaseSproutImportElementImporter
{
	/** @var string Session key for storing the cart number */
	protected $cookieCartId = 'commerce_cookie';

	/**
	 * @return string
	 */
	public function getName()
	{
		return Craft::t("Craft Commerce Orders");
	}

	/**
	 * @return mixed
	 */
	public function getModelName()
	{
		return 'Commerce_Order';
	}

	public function setModel($model, $settings = array())
	{
		$this->model = parent::setModel($model, $settings);

		$this->model->isCompleted = (!empty($settings['isCompleted'])) ? $settings['isCompleted'] : 0;

		if (empty($this->model->number))
		{
			$this->model->number = $this->getRandomCartNumber();
		}

		$this->model->paymentCurrency = craft()->commerce_paymentCurrencies->getPrimaryPaymentCurrencyIso();
	}

	public function save()
	{
		$order = $this->model;

		$result = craft()->commerce_orders->saveOrder($order);

		if (!$result)
		{
			$message = Craft::t("Could not save order attributes.");

			SproutImportPlugin::log($message, LogLevel::Error);

			sproutImport()->addError($message, 'invalid-attributes');
		}

		if (!empty($this->rows['lineItems']))
		{
			foreach ($this->rows['lineItems'] as $lineItem)
			{
				$purchasableId = $lineItem['purchasableId'];
				$qty           = $lineItem['qty'];
				$options       = $lineItem['options'];

				if (!craft()->commerce_cart->addToCart($order, $purchasableId, $qty, $options))
				{

				}
			}
		}

		if (!empty($this->rows['payments']))
		{
			$paymentData = $this->rows['payments'];

			$paymentMethod = $order->getPaymentMethod();
			//
			//$paymentForm = $paymentMethod->getPaymentFormModel();
			//
			//// Needed for the base class populateModelFromPost
			//$_POST = $paymentData;
			//
			//$paymentForm->populateModelFromPost($paymentData);
			//
			//$paymentForm->validate();

			//if (!$paymentForm->hasErrors())

			try
			{
				if (!$order->isCompleted)
				{
					craft()->commerce_orders->completeOrder($order);
				}

				//creating order, transaction and request
				$transaction = craft()->commerce_transactions->createTransaction($order);

				if (!empty($this->rows['payments']['status']))
				{
					$transaction->status = $this->rows['payments']['status'];
				}

				$transaction->type = Commerce_TransactionRecord::TYPE_AUTHORIZE;

				if (!empty($this->rows['payments']['type']))
				{
					$transaction->type = $this->rows['payments']['type'];
				}

				if (!empty($this->rows['payments']['reference']))
				{
					$transaction->reference = $this->rows['payments']['reference'];
				}

				if (!empty($this->rows['payments']['dateUpdated']))
				{
					$transaction->dateUpdated = $this->rows['payments']['dateUpdated'];
				}

				if (!empty($this->rows['payments']['message']))
				{
					$transaction->message = $this->rows['payments']['message'];
				}

				craft()->commerce_transactions->saveTransaction($transaction);

				craft()->commerce_orders->updateOrderPaidTotal($order);

				$success = true;
			}
			catch (\Exception $e)
			{
				$message = $e->getMessage();

				SproutImportPlugin::log($message, LogLevel::Error);

				sproutImport()->addError($message, 'payment-invalid');

				$success = false;
			}
		}

		$this->saveAddress('billingAddress', $order);
		$this->saveAddress('shippingAddress', $order);

		return $success;
	}

	private function saveAddress($type = 'billingAddress', Commerce_OrderModel $order)
	{
		if (!empty($this->rows['addresses'][$type]))
		{
			$address = $this->rows['addresses'][$type];

			$addressModel = Commerce_AddressModel::populateModel($address);

			if (craft()->commerce_addresses->saveAddress($addressModel))
			{
				$addressId = $addressModel->id;

				$keyAddress = $type . 'Id';

				$order->$keyAddress = $addressId;

				craft()->commerce_orders->saveOrder($order);
			}
		}
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public function deleteById($id)
	{
		return craft()->elements->deleteElementById($id);
	}

	public function getImporterDataKeys()
	{
		return array('lineItems', 'payments', 'addresses');
	}

	/**
	 * This is code is from Commerce_CartService copied it because it is private
	 *
	 * @return mixed|string
	 */
	private function getRandomCartNumber()
	{
		$number = md5(uniqid(mt_rand(), true));

		$order = craft()->commerce_orders->getOrderByNumber($number);
		// Make sure not duplicate number
		if ($order)
		{
			return $this->getRandomCartNumber();
		}

		return $number;
	}
}