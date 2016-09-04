<?php
namespace Craft;

class Commerce_OrderSproutImportElementImporter extends BaseSproutImportElementImporter
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return "Craft Commerce Orders";
	}

	/**
	 * @return mixed
	 */
	public function getModelName()
	{
		return 'Commerce_Order';
	}

	/**
	 * @return mixed
	 */
	public function save()
	{
		if ($this->model->number == null)
		{
			$this->model->number = $this->getRandomCartNumber();
		}

		if ($this->model->returnUrl != null)
		{
			// Change number variable to generated number.
			$this->model->returnUrl = str_replace('{number}', $this->model->number, $this->model->returnUrl);
		}
		$dataLineItems = $this->data['lineItems'];

		// Get data for saving after calculating adjustments
		$isCompleted = $this->model->isCompleted;

		// Needed to calculate adjustments such totalPrice and itemTotal
		$this->model->isCompleted = false;

		$result = craft()->commerce_orders->saveOrder($this->model);

		$order = $this->model;

		if (!empty($dataLineItems))
		{
			foreach ($dataLineItems as $dataLineItem)
			{
				$lineItem = craft()->commerce_lineItems->createLineItem($dataLineItem['purchasableId'], $order,
					$dataLineItem['options'], $dataLineItem['qty']);

				$isNewLineItem = true;

				$lineItem->validate();

				$lineItem->purchasable->validateLineItem($lineItem);

				if (!$lineItem->hasErrors())
				{
					if (craft()->commerce_lineItems->saveLineItem($lineItem))
					{
						if ($isNewLineItem)
						{
							$linesItems   = $order->getLineItems();
							$linesItems[] = $lineItem;
							$order->setLineItems($linesItems);
						}
					}
				}
			}
		}

		craft()->commerce_orders->saveOrder($order);

		$paymentData = $this->data['payments'];

		$paymentMethod = $order->getPaymentMethod();

		$paymentForm = $paymentMethod->getPaymentFormModel();

		// Needed for the base class populateModelFromPost
		$_POST = $paymentData;

		$paymentForm->populateModelFromPost($paymentData);

		$paymentForm->validate();
		if (!$paymentForm->hasErrors())
		{
			//Craft::dd($paymentForm);
			$success = craft()->commerce_payments->processPayment($order, $paymentForm);
		}
		else
		{
			$message = Craft::t('Payment information submitted is invalid.');

			SproutImportPlugin::log($message, LogLevel::Error);

			sproutImport()->addError($message, 'payment-invalid');

			$success = false;
		}

		return $success;
		// Update the order again for isCompleted attribute only after adjustments
		//$order->isCompleted = $isCompleted;
		//return craft()->commerce_orders->updateOrderPaidTotal($order);
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

	public function defineKeys()
	{
		return array('lineItems', 'payments');
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