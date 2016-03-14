<?php

namespace Craft;
class EntrySproutImportImporter extends ElementSproutImportImporter
{
	public function getModel()
	{
		$model = 'Craft\\EntryModel';
		return new $model;
	}

	public function save()
	{
		return craft()->entries->saveEntry($this->model);
	}
}