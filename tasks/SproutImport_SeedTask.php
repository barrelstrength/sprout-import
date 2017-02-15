<?php
namespace Craft;

class SproutImport_SeedTask extends BaseTask
{
	/**
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Sprout Import Seed Task');
	}

	/**
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'seedTasks' => AttributeType::Mixed
		);
	}

	/**
	 *
	 * @return mixed
	 */
	public function getTotalSteps()
	{
		$seedTasks = $this->getSettings()->getAttribute('seedTasks');

		return $seedTasks['quantity'];
	}

	/**
	 * Create 1 mockData for each step
	 *
	 * @param int $step
	 *
	 * @return bool
	 */
	public function runStep($step)
	{
		craft()->config->maxPowerCaptain();

		$seedTasks = $this->getSettings()->getAttribute('seedTasks');

		try
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

			$details = $seedTasks['details'];

			$weedModelAttributes = array(
				'seed'          => true,
				'type'          => $seedTasks['type'],
				'details'       => $details,
				'dateSubmitted' => $seedTasks['dateSubmitted']
			);

			$weedModel = SproutImport_WeedModel::populateModel($weedModelAttributes);

			$elementType = $seedTasks['elementType'];
			$settings    = $seedTasks['settings'];

			$namespace = 'Craft\\' . $elementType . 'SproutImportElementImporter';

			$importerClass = new $namespace;

			$seed = $importerClass->getMockData(1, $settings);

			sproutImport()->save($seed, $weedModel);

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
