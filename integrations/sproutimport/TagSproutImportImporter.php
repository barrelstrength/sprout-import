<?php

namespace Craft;


class TagSproutImportImporter extends ElementSproutImportImporter
{

	public function isElement()
	{
		return true;
	}

	public function getModel()
	{
		$model = 'Craft\\TagModel';
		return new $model;
	}

	public function save()
	{
		return craft()->tags->saveTag($this->model);
	}
}