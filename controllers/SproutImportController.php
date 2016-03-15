<?php
namespace Craft;

class SproutImportController extends BaseController
{
	public function actionImportElements()
	{
		$this->requirePostRequest();

		$tasks = array();
		$files = UploadedFile::getInstancesByName('files');

		foreach ($files as $file)
		{
			if (!$file->getHasError() && $file->getType() == 'application/json'
			|| $file->getType() == 'application/octet-stream')
			{
				$path = craft()->path->getStoragePath() . $file->getName();

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
		catch(\Exception $e)
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

	public function actionGenerateData()
	{
		$elementSelect = array();

		$elementSelect['Entry']    = 'Entries';
		$elementSelect['Category'] = 'Categories';
		$elementSelect['Tag']      = 'Tags';

		craft()->templates->includeJsResource('sproutimport/js/sproutimport.js');

		$sections = sproutImport()->element->getChannelSections();
		$single   = array('single' => 'Single');

		$sections = array_merge($single, $sections);

		$this->renderTemplate('sproutimport/generatedata', array(
			'elements' => $elementSelect,
			'sections' => $sections
		));
	}

	public function actionGenerateElements()
	{
		$this->requirePostRequest();

		$elementType = craft()->request->getRequiredPost('elementType');
		$settings = craft()->request->getRequiredPost('settings');

		if(!empty($elementType))
		{
			$className = sproutImport()->getImporterByName($elementType);

			$importerClass = new $className;

			$importerClass->getMockData($settings);
		}

		craft()->userSession->setNotice(Craft::t('Elements generated.' . $elementType));

		$this->redirectToPostedUrl();
	}
}
