<?php
namespace Craft;

class RichTextSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getFieldTypeModelName()
	{
		return 'RichTextFieldType';
	}

	/**
	 * @return bool
	 */
	public function canMockData()
	{
		return true;
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
