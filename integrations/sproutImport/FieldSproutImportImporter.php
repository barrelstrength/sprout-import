<?php
namespace Craft;

class FieldSproutImportImporter extends SproutImportBaseImporter
{
	public function getModel()
	{
		$handle = $this->settings['handle'];
		$exist = craft()->fields->getFieldByHandle($handle);

		if($exist != null)
		{
			return $exist;
		}
		else
		{
			$model = 'Craft\\FieldModel';
			return new $model;
		}
	}

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
