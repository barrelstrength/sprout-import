<?php

namespace Craft;

abstract class SproutImportBaseElementImporter extends SproutImportBaseImporter
{
	public function setModel($model)
	{
		$this->model = $model;
	}
	public function deleteById($id)
	{
		return craft()->elements->deleteElementById($id);
	}
}