<?php
namespace Craft;

class SproutImport_SettingsImporterService extends BaseApplicationComponent
{
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
		$modelName     = sproutImport()->getImporterModelName($settings);
		$importerClass = sproutImport()->getImporterByModelName($modelName, $settings);

		$model = $importerClass->getModel();

		if ($model->validate())
		{
			try
			{
				$saved = $importerClass->save();

				if ($saved)
				{
					// Get updated model after save
					$model = $importerClass->getModel();

					$importerModel = sproutImport()->getImporterModelName($settings);

					$event = new Event($this, array(
						'element' => $model,
						'seed'    => $seed,
						'@model'  => $importerModel
					));

					sproutImport()->onAfterImportSetting($event);

					$importerClass->resolveNestedSettings($model, $settings);

					return $model;
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
}