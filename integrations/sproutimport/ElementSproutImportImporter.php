<?php

namespace Craft;


class ElementSproutImportImporter extends SproutImportBaseImporter
{

	private $elementName;

	public function __construct($settings = array(), $name)
	{
		$this->elementName = $name;
		parent::__construct($settings);
	}

	public function getModel()
	{

		$elementName = $this->elementName;

		$model = "Craft\\{$elementName}Model";
		return new $model;
	}

	public function deleteById($id)
	{
		return craft()->elements->deleteElementById($id);
	}

	public function save()
	{
		return craft()->elements->saveElement($this->model);
	}
}