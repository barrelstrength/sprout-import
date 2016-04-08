<?php
namespace Craft;

class PlainTextSproutImportFieldImporter extends BaseSproutImportFieldImporter
{

	public function getMockData()
	{
		$settings = $this->fieldModel->settings;

		if ($settings != null && $settings['multiline'] == 1)
		{
			$rows = $settings['initialRows'];
			if ($rows > 1)
			{
				$sentences = $this->fakerService->sentences($rows);

				return implode("\n", $sentences);
			}
		}

		return $this->fakerService->word;
	}

}
