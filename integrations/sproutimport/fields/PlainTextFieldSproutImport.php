<?php
namespace Craft;

class PlainTextFieldSproutImport extends BaseFieldSproutImport
{

	public function getMockData()
	{
		return $this->fakerService->word;
	}

}
