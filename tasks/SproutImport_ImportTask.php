<?php
namespace Craft;

class SproutImport_ImportTask extends BaseTask
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
		$data  = $step ? $files[$step] : $files[0];

		$content = $data['content'];
		$file    = $data['path'];

		if ($content && ($elements = json_decode($content, true)) && !json_last_error())
		{
			try
			{
				$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

				$filename = substr($file, strrpos($file, '/') + 1);

				sproutImport()->save($elements, $seed, $filename);

				IOHelper::deleteFile($file);

				$errors = sproutImport()->getErrors();

				if (!empty($errors))
				{
					$msg = implode("\n", $errors);
					sproutImport()->error($msg);

					$transaction->rollback();

					return false;
				}

				if ($transaction && $transaction->active)
				{
					$transaction->commit();
				}

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
