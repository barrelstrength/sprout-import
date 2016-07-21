<?php
namespace Craft;

class Commerce_OrderSproutImportElementImporter extends BaseSproutImportElementImporter
{
	/**
	 * @return mixed
	 */
	public function defineModel()
	{
		return 'Commerce_OrderModel';
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
							$linesItems = $order->getLineItems();
							$linesItems[] = $lineItem;
							$order->setLineItems($linesItems);
						}
					}
				}
			}
		}

		craft()->commerce_orders->saveOrder($order);

		// Update the order again for isCompleted attribute only after adjustments
		$order->isCompleted = $isCompleted;
		return craft()->commerce_orders->updateOrderPaidTotal($order);
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

	/**
	 * @return string
	 */
	public function getSettingsHtml()
	{
	}

	/**
	 * @param $settings
	 */
	public function getMockData($settings)
	{
	}

	public function defineKeys()
	{
		return array('lineItems');
	}

	/**
	 * This is code is from Commerce_CartService copied it because it is private
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