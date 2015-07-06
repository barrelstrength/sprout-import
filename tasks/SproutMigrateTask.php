<?php
namespace Craft;

class SproutMigrateTask extends BaseTask
{
	public function runStep($step)
	{
		craft()->config->maxPowerCaptain();

		$files = $this->getSettings()->getAttribute('files');
		$file  = $step ? $files[$step] : $files[0];

		$content = file_get_contents($file);

		if ($content && ($elements = json_decode($content, true)) && !json_last_error())
		{
			try
			{
				$result = sproutMigrate()->save($elements);

				IOHelper::deleteFile($file);

				sproutMigrate()->log('Task result for '.$file, $result);

				return true;
			}
			catch (\Exception $e)
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
		return 'Sprout Migrate Task';
	}
}
