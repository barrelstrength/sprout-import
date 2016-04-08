<?php
namespace Craft;

class PlainTextSproutImportFieldImporter extends BaseSproutImportFieldImporter
{

	public function getMockData()
	{
		return $this->fakerService->word;
	}

}
