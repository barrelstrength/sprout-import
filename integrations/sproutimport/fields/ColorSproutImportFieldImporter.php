<?php
namespace Craft;

class ColorSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'Color';
	}

	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		return $this->fakerService->hexcolor;
	}
}
