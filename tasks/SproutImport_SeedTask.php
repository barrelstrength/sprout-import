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
			'seedTask' => AttributeType::Mixed
		);
	}

	/**
	 *
	 * @return mixed
	 */
	public function getTotalSteps()
	{
		$seedTask = $this->getSettings()->getAttribute('seedTask');

		return $seedTask['quantity'];
	}

	/**
	 * Create the mock data each time a step is run
	 *
	 * @param int $step
	 *
	 * @return bool
	 */
	public function runStep($step)
	{
		craft()->config->maxPowerCaptain();

		$seedTask = $this->getSettings()->getAttribute('seedTask');

		try
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

			$details = $seedTask['details'];

			$weedModelAttributes = array(
				'seed'          => true,
				'type'          => $seedTask['type'],
				'details'       => $details,
				'dateSubmitted' => $seedTask['dateSubmitted']
			);

			$weedModel = SproutImport_WeedModel::populateModel($weedModelAttributes);

			$elementType = $seedTask['elementType'];
			$settings    = $seedTask['settings'];

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
