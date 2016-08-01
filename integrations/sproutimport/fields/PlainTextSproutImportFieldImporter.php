<?php
namespace Craft;

class PlainTextSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'PlainText';
	}

	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->model->settings;

		if ($settings != null && $settings['multiline'] == 1)
		{
			$lines      = rand(1, 3);
			$paragraphs = $this->fakerService->paragraphs($lines);

			return implode("\n\n", $paragraphs);
		}

		$lines     = rand(2, 4);
		$sentences = $this->fakerService->sentences($lines);

		return implode("\n ", $sentences);
	}
}
