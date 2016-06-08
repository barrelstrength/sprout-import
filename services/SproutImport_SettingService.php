<?php
namespace Craft;

class SproutImport_SettingService extends BaseApplicationComponent
{
	private $savedIds = array();

	/**
	 * Returns $model if saved, or false if failed
	 * This can be called in a loop, or called directly if we know we just have one setting and want and ID back.
	 *
	 * @param      $settings
	 * @param bool $seed
	 *
	 * @return bool
	 */
	public function saveSetting($settings, $seed = false)
	{
		if ($seed)
		{
			craft()->sproutImport_seed->seed = true;
		}

		$importerClass = sproutImport()->getImporterByRow($settings);

		$model = $importerClass->getPopulatedModel();

		if ($model->validate())
		{
			if (craft()->sproutImport_seed->seed)
			{
				craft()->sproutImport_seed->trackSeed($importerClass->model->id, sproutImport()->getImporterModel($settings));
			}

			try
			{
				$saved = $importerClass->save();
			}
			catch (\Exception $e)
			{
				$message = Craft::t("Error on importer save setting method. \n ");
				$message.= $e->getMessage();

				sproutImport()->addError($message, 'save-setting-importer');

				return false;
			}

			if ($saved)
			{
				$importerClass->resolveNestedSettings($model, $settings);
			}

			$this->savedIds[] = $model->id;

			return $importerClass->model;
		}
		else
		{
			$errorKey = serialize($model->getAttributes());

			$errorLog = array();
			$errorLog['errors']     = Craft::t("Unable to save settings.");
			$errorLog['attributes'] = $model->getAttributes();

			sproutImport()->addError($errorLog, $errorKey);

			return false;
		}
	}

	/**
	 * @return array
	 */
	public function getSavedResults()
	{
		$result = array(
			'savedSettingIds' => $this->savedIds
		);

		return $result;
	}
}