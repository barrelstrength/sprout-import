<?php
namespace Craft;

class SproutImport_SettingsTask extends BaseTask
{
	public function runStep($step)
	{
		craft()->config->maxPowerCaptain();

		$seed = $this->getSettings()->getAttribute('seed');

		$files = $this->getSettings()->getAttribute('files');
		$file  = $step ? $files[$step] : $files[0];

		if ($content = sproutImport()->getJson($file))
		{
			if ($content = sproutImport()->getJson($file))
			{
				// @TODO - make logic around parsing settings more robust
				$settings = $content['@settings'];

				try
				{
					// @TODO - add control for $trackSeeds via setting
					$result = sproutImport()->saveSettings($settings, $seed);

					IOHelper::deleteFile($file);

					sproutImport()->log('Task result for ' . $file, $result);

					return true;
				} catch (\Exception $e)
				{
					sproutImport()->error($e->getMessage());
				}
			}
			else
			{
				sproutImport()->error('Unable to parse file.', compact('file'));
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
			'files' => AttributeType::Mixed,
			'seed'  => AttributeType::Bool
		);
	}

	protected function getDescriptions()
	{
		return 'Sprout Import Settings Task';
	}
}
