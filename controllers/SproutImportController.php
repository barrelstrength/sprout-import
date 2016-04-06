<?php
namespace Craft;

class SproutImportController extends BaseController
{
	public function actionImportElements()
	{
		$this->requirePostRequest();

		$tasks = array();
		$files = UploadedFile::getInstancesByName('files');

		$folderPath = sproutImport()->createTempFolder();

		foreach ($files as $file)
		{
			if (!$file->getHasError() && $file->getType() == 'application/json'
				|| $file->getType() == 'application/octet-stream'
			)
			{
				$path = $folderPath . $file->getName();

				if (move_uploaded_file($file->getTempName(), $path))
				{
					$tasks[] = $path;
				}
			}
		}

		$seed = craft()->request->getPost('seed');

		try
		{
			sproutImport()->createImportTasks($tasks, $seed);

			craft()->userSession->setNotice(Craft::t('Files queued for import. Total: {tasks}', array(
				'tasks' => count($tasks)
			)));
		}
		catch (\Exception $e)
		{
			craft()->userSession->setError($e->getMessage());
		}

		$this->redirectToPostedUrl();
	}

	public function actionEnqueueTasksByPost()
	{
		$elements = craft()->request->getPost('elements');

		sproutImport()->setEnqueueTasksByPost($elements);
		craft()->end();
	}

	public function actionGenerateElements()
	{
		$this->requirePostRequest();

		$elementType = craft()->request->getRequiredPost('elementType');
		$settings    = craft()->request->getRequiredPost('settings');

		if (!empty($elementType))
		{
			$className = sproutImport()->getImporterByName($elementType);

			$importerClass = new $className;

			$ids = $importerClass->getMockData($settings);

			if (!empty($ids))
			{
				foreach ($ids as $id)
				{
					sproutImport()->seed->trackSeed($id, $elementType, 'fake');
				}
			}
		}

		craft()->userSession->setNotice(Craft::t('Elements generated.'));

		$this->redirectToPostedUrl();
	}

	public function actionSeedTemplate()
	{
		$elementSelect = array();

		$elementSelect['Entry']    = 'Entries';
		$elementSelect['Category'] = 'Categories';
		$elementSelect['Tag']      = 'Tags';

		$importers = sproutImport()->getSproutImportImporters();

		$settingElements = "";

		if (!empty($importers))
		{
			foreach ($importers as $importer)
			{
				if ($importer->isElement())
				{
					$settingElements .= $importer->getMockSettings() . "\n";
				}
			}
		}

		craft()->templates->includeJsResource('sproutimport/js/sproutimport.js');

		$this->renderTemplate('sproutimport/seed', array(
			'elements' => $elementSelect,
			'settings' => array(
				'elements' => TemplateHelper::getRaw($settingElements)
			)
		));
	}
}
