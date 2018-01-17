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
		return Craft::t('Commerce Orders');
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

		$this->model->setAttributes($settings['attributes']);

		$this->model->isCompleted = (!empty($settings['isCompleted'])) ? $settings['isCompleted'] : 0;

		if (empty($this->model->number))
		{
			$this->model->number = $this->getRandomCartNumber();
		}

		$this->model->paymentCurrency = craft()->commerce_paymentCurrencies->getPrimaryPaymentCurrencyIso();

		//Bypass validation
		$this->model->customerId = 0;
	}

	public function save()
	{
		$settings = $this->rows;

		if (!empty($customer = $settings['attributes']['customerId']))
		{
			// If email is passed create customer record
			if (!is_int($customer))
			{
				if (!filter_var($customer, FILTER_VALIDATE_EMAIL))
				{
					$message = Craft::t($customer . " email does not validate");

					SproutImportPlugin::log($message, LogLevel::Error);

					sproutImport()->addError($message, 'invalid-email');
				}
				else
				{
					$user = craft()->users->getUserByEmail($customer);

					$userId = ($user != null)? $user->id : null;

					$attributes = array(
						'email'  => $customer,
						'userId' => $userId
					);

					$customerModel = Commerce_CustomerModel::populateModel($attributes);

					$result = craft()->commerce_customers->saveCustomer($customerModel);

					if ($result)
					{
						$this->model->customerId = $customerModel->id;
					}
				}
			}
		}

		$order = $this->model;

		$result = craft()->commerce_orders->saveOrder($order);

		if (!$result)
		{
			$message = $order->getErrors();

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

		if (!empty($this->rows['transactions']))
		{
			try
			{
				if (!$order->isCompleted)
				{
					craft()->commerce_orders->completeOrder($order);
				}

				if (!empty($orderStatusId = $settings['attributes']['orderStatusId']))
				{
					// Needed to import Status Id
					$order->orderStatusId = $orderStatusId;
					$order->dateOrdered   = $settings['attributes']['dateOrdered'];
				}

				//creating order, transaction and request
				$transaction = craft()->commerce_transactions->createTransaction($order);

				$transaction->setAttributes($this->rows['transactions']);

				if (empty($this->rows['transactions']['type']))
				{
					// Capture and Purchase type will set total paid other type does not
					$transaction->type = Commerce_TransactionRecord::TYPE_CAPTURE;
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

		$order = craft()->commerce_orders->getOrderByNumber($number);
		// Make sure not duplicate number
		if ($order)
		{
			return $this->getRandomCartNumber();
		}

		return $number;
	}
}