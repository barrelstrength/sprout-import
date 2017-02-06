<?php
namespace Craft;

class SproutImport_SeedTask extends BaseTask
{
	/**
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Sprout Seed Task');
	}

	/**
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'seeds'     => AttributeType::Mixed,
			'seedInfo'  => AttributeType::Mixed
		);
	}

	/**
	 * @return mixed
	 */
	public function getTotalSteps()
	{
		return count($this->getSettings()->getAttribute('seeds'));
	}

	/**
	 * @param int $step
	 *
	 * @return bool
	 */
	public function runStep($step)
	{
		craft()->config->maxPowerCaptain();

		$seeds    = $this->getSettings()->getAttribute('seeds');
		$seedInfo = $this->getSettings()->getAttribute('seedInfo');

		$elements  = $step ? $seeds[$step] : $seeds[0];

		try
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

			$details = $seedInfo['details'];

			$weedModelAttributes = array(
				'seed'    => true,
				'type'    => $seedInfo['type'],
				'details' => $details,
				'dateSubmitted' => $seedInfo['dateSubmitted']
			);

			$weedModel = SproutImport_WeedModel::populateModel($weedModelAttributes);

			sproutImport()->save($elements, $weedModel);

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
