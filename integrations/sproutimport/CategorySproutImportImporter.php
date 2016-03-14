<?php

namespace Craft;


class CategorySproutImportImporter extends ElementSproutImportImporter
{
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