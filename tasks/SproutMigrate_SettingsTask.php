<?php
namespace Craft;

class SproutMigrate_SettingsTask extends BaseTask
{
	public function runStep($step)
	{
		craft()->config->maxPowerCaptain();

		$files = $this->getSettings()->getAttribute('files');
		$file  = $step ? $files[$step] : $files[0];

		if ($content = sproutMigrate()->getJsonContent($file))
		{
			if ($content = sproutMigrate()->getJsonContent($file))
			{
				// @TODO - make logic around parsing settings more robust
				$settings = $content['@settings'];

				try
				{
					$result = sproutMigrate()->saveSettings($settings);

					IOHelper::deleteFile($file);

					sproutMigrate()->log('Task result for ' . $file, $result);

					return true;
				} catch (\Exception $e)
				{
					sproutMigrate()->error($e->getMessage());
				}
			}
			else
			{
				sproutMigrate()->error('Unable to parse file.', compact('file'));
			}

			return false;
		}
	}

	public function getTotalSteps()
	{
		return count($this->getSettings()->getAttribute('files'));
	}

	protected function defineSettings()
	{
		return array(
			'files' => AttributeType::Mixed
		);
	}

	protected function getDescriptions()
	{
		return 'Sprout Migrate Settings Task';
	}
}
