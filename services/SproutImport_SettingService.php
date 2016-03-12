<?php

namespace Craft;


class SproutImport_SettingService extends BaseApplicationComponent
{

	private $savedIds = array();

	// Returns $model if saved, or false if failed
	// This can be called in a loop, or called directly if we know we just have one setting and want and ID back.
	public function saveSetting($settings, $seed = false)
	{
		if ($seed)
		{
			craft()->sproutImport_seed->seed = true;
		}

		$importer = $this->getImporter($settings);

		if ($importer->isValid() && $importer->save())
		{
			if (craft()->sproutImport_seed->seed)
			{
				craft()->sproutImport_seed->trackSeed($importer->model->id, sproutImport()->getImporterModel($settings));
			}

			// @todo - probably want to protect $importer->model and update to $importer->getModel()
			$importer->resolveNestedSettings($importer->model, $settings);

			$this->savedIds[] = $importer->model->id;

			// @todo - keep track of what we've saved for reporting later.
			sproutImport()->log('Saved ID: ' . $importer->model->id);

			return $importer->model;
		}
		else
		{
			sproutImport()->error('Unable to validate.');
			sproutImport()->error($importer->getErrors());

			return false;
		}
	}

	public function getSavedResults()
	{
		return array(
			'savedSettingIds' => $this->savedIds
		);
	}


	public function getImporter($settings)
	{
		$importerModel = sproutImport()->getImporterModel($settings);

		$importerClassName = 'Craft\\' . $importerModel . 'SproutImportImporter';

		$importerClass = new $importerClassName($settings);

		return $importerClass;
	}

}