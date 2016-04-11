<?php
namespace Craft;

class SproutImportTask extends BaseTask
{
	/**
	 * @return string
	 */
	public function getDescription()
	{
		return 'Sprout Import Task';
	}

	/**
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'files' => AttributeType::Mixed,
			'seed'  => AttributeType::Bool
		);
	}

	/**
	 * @return mixed
	 */
	public function getTotalSteps()
	{
		return count($this->getSettings()->getAttribute('files'));
	}

	/**
	 * @param int $step
	 *
	 * @return bool
	 */
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
}
