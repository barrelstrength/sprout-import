<?php
namespace Craft;

class RichTextSproutImportFieldImporter extends BaseSproutImportFieldImporter
{

	public function getMockData()
	{

		$lines = rand(3, 5);
		$paragraphs = $this->fakerService->paragraphs($lines);

		return implode("\n\n", $paragraphs);
	}

}
