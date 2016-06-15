<?php
namespace Craft;

class ColorSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		return $this->fakerService->hexcolor;
	}
}
