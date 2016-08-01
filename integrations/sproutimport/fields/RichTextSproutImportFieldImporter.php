<?php
namespace Craft;

class RichTextSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'RichText';
	}

	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$lines      = rand(3, 5);
		$paragraphs = $this->fakerService->paragraphs($lines);

		return implode("\n\n", $paragraphs);
	}
}
