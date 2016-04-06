<?php
namespace Craft;

class UserSproutImportImporter extends SproutImportBaseElementImporter
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