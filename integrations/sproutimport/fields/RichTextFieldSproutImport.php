<?php
namespace Craft;

class RichTextFieldSproutImport extends BaseFieldSproutImport
{

	public function getMockData()
	{
		return $this->fakerService->paragraph();
	}

}
