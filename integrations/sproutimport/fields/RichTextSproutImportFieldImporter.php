<?php
namespace Craft;

class RichTextSproutImportFieldImporter extends BaseSproutImportFieldImporter
{

	public function getMockData()
	{
		return $this->fakerService->paragraph();
	}

}
