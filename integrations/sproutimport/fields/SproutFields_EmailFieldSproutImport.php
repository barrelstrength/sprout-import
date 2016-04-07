<?php
namespace Craft;

class SproutFields_EmailFieldSproutImport extends BaseFieldSproutImport
{

	public function getMockData()
	{
		return $this->fakerService->email;
	}

}
