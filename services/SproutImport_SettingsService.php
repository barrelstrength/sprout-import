<?php
namespace Craft;

class SproutImport_SettingsService extends BaseApplicationComponent
{
	/**
	 * @type array
	 */
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
	public function saveSetting($settings, $seed = false, $source = 'import')
	{
		$modelName     = sproutImport()->getImporterModelName($settings);
		$importerClass = sproutImport()->getImporterByModelName($modelName, $settings);

		$model = $importerClass->getModel();

		if ($model->validate())
		{
			try
			{
				$importerClass->setData($settings);

				$saved = $importerClass->save();

				if ($saved)
				{
					// Get updated model after save
					$model = $importerClass->getModel();

					$importerModel = sproutImport()->getImporterModelName($settings);

					$eventParams = array(
						'element' => $model,
						'seed'    => $seed,
						'@model'  => $importerModel,
						'source'  => $source
					);

					$event = new Event($this, $eventParams);

					sproutImport()->onAfterImportSetting($event);
				}
			}
			catch (\Exception $e)
			{
				$message = Craft::t("Error on importer save setting method. \n ");
				$message .= $e->getMessage();

				SproutImportPlugin::log($message, LogLevel::Error);
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

			$errorLog               = array();
			$errorLog['errors']     = Craft::t("Unable to save settings.");
			$errorLog['attributes'] = $model->getAttributes();

			SproutImportPlugin::log($errorLog['errors'], LogLevel::Error);
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