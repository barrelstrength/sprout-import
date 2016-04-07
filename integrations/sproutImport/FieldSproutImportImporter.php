<?php
namespace Craft;

class FieldSproutImportImporter extends BaseSproutImportImporter
{

	public function getObjectByHandle($handle)
	{
		return craft()->fields->getFieldByHandle($handle);
	}

	public function defineModel()
	{
		return 'Craft\\FieldModel';
	}
	//
	//public function getModel($fieldService = null)
	//{
	//	$handle = $this->settings['handle'];
	//
	//	if ($fieldService == null)
	//	{
	//		$fieldService = craft()->fields;
	//	}
	//
	//	$exist = $fieldService->getFieldByHandle($handle);
	//
	//	if($exist != null)
	//	{
	//		return $exist;
	//	}
	//	else
	//	{
	//		$model = 'Craft\\FieldModel';
	//		return new $model;
	//	}
	//}

	//public function populateModel($model, $settings)
	//{
	//	// @TODO - require groupId or set fallback.
	//	// Let import override the field context
	//
	//	// Assign any setting values we can to the model
	//	//$model->setAttributes($settings);
	//	//
	//	//return $model;
	//}

	public function save()
	{
		return craft()->fields->saveField($this->model);
	}

	public function deleteById($id)
	{
		return craft()->fields->deleteFieldById($id);
	}
}
