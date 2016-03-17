<?php

namespace Craft;


class UserSproutImportImporter extends ElementSproutImportImporter
{

	public function isElement()
	{
		return true;
	}

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