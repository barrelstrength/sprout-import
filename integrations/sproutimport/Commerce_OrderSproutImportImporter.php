<?php
namespace Craft;

class Commerce_OrderSproutImportImporter extends BaseSproutImportImporter
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
		return craft()->commerce_orders->saveOrder($this->model);
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
	 * @param $model
	 * @param $settings
	 */
	public function populateModel($model, $settings)
	{

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
}