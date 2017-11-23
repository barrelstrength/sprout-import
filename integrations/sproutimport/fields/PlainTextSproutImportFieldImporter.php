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

		if ($settings != null && isset($settings['multiline']) && $settings['multiline'] == 1)
		{
			$lines      = rand(1, 3);
			$paragraphs = $this->fakerService->paragraphs($lines);

			$string = implode("\n\n", $paragraphs);

			return ($settings['maxLength']) ? substr($string, 0, $settings['maxLength']) : $string;
		}

		$lines     = rand(2, 4);
		$sentences = $this->fakerService->sentences($lines);

		$string = implode("\n\n", $sentences);

		return ($settings['maxLength']) ? substr($string, 0, $settings['maxLength']) : $string;
	}
}
