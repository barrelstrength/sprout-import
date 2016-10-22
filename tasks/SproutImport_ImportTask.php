<?php
namespace Craft;

class SproutImport_ImportTask extends BaseTask
{
	/**
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Sprout Import Task');
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

		$elements = $data['content'];
		$file     = $data['path'];

		try
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

			// remove any initial slash from the filename
			$filename = ($file != 'pastedJson') ? substr($file, strrpos($file, '/') + 1) : $file;

			sproutImport()->save($elements, $seed, $filename);

			IOHelper::deleteFile($file);

			$errors = sproutImport()->getErrors();

			if (!empty($errors))
			{
				$message = implode("\n", $errors);

				SproutImportPlugin::log($message, LogLevel::Error);

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
			SproutImportPlugin::log($e->getMessage(), LogLevel::Error);
		}

		return false;
	}
}
