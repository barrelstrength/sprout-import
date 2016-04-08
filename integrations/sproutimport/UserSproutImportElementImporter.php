<?php
namespace Craft;

class UserSproutImportElementImporter extends BaseSproutImportElementImporter
{
	public function getModel()
	{
		$model = 'Craft\\UserModel';

		return new $model;
	}

	public function save()
	{
		return craft()->users->saveUser($this->model);
	}
}