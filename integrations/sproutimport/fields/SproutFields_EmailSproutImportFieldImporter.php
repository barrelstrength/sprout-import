<?php
namespace Craft;

class SproutFields_EmailSproutImportFieldImporter extends BaseSproutImportFieldImporter
{

	public function getMockData()
	{
		return $this->fakerService->email;
	}

}
