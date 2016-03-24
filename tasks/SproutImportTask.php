<?php
namespace Craft;

class SproutImportTask extends BaseTask
{
	public function runStep($step)
	{
		craft()->config->maxPowerCaptain();

		$seed = $this->getSettings()->getAttribute('seed');

		$files = $this->getSettings()->getAttribute('files');
		$file  = $step ? $files[$step] : $files[0];

		$content = file_get_contents($file);

		if ($content && ($elements = json_decode($content, true)) && !json_last_error())
		{
			try
			{
				$result = sproutImport()->save($elements, $seed);

				IOHelper::deleteFile($file);

				sproutImport()->log('Task result for ' . $file, $result);

				return true;
			}
			catch (\Exception $e)
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
		return 'Sprout Import Task';
	}
}
