<?php

namespace Craft;


class CategorySproutImportImporter extends ElementSproutImportImporter
{

	public function isElement()
	{
		return true;
	}

	public function getMockSettings()
	{
		$variables = array();
		return craft()->templates->render('sproutimport/settings/_category', $variables);
	}

	public function getModel()
	{
		$model = 'Craft\\CategoryModel';
		return new $model;
	}

	public function save()
	{
		return craft()->categories->saveCategory($this->model);
	}
}