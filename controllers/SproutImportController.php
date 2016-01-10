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
			// @todo - why do we have 'application/octet-stream' here?
			// From the answer below, it sounds like this may indicate some file we were importing had the wrong format?
			// http://stackoverflow.com/questions/20508788/do-i-need-content-type-application-octet-stream-for-file-download
			if (!$file->getHasError() && $file->getType() == 'application/json'
			|| $file->getType() == 'application/octet-stream')
			{
				$path = craft()->path->getPluginsPath().'sproutimport/downloads/'.$file->getName();

				if (move_uploaded_file($file->getTempName(), $path))
				{
					$tasks[] = $path;
				}
			}
		}

		try
		{
			sproutImport()->createImportElementsTasks($tasks);

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

	public function actionImportSettings()
	{
		$this->requirePostRequest();

		$seed = craft()->request->getRequiredPost('seed');

		$tasks = array();
		$files = UploadedFile::getInstancesByName('files');

		foreach ($files as $file)
		{
			if (!$file->getHasError() && $file->getType() == 'application/json'
					|| $file->getType() == 'application/octet-stream')
			{
				$path = craft()->path->getPluginsPath().'sproutimport/downloads/'.$file->getName();

				if (move_uploaded_file($file->getTempName(), $path))
				{
					$tasks[] = $path;
				}
			}
		}

		try
		{
			sproutImport()->createImportSettingsTasks($tasks, $seed);

			craft()->userSession->setNotice(Craft::t('Files queued for import. Total: {tasks}', array(
				'tasks' => count($tasks)
			)));
		}
		catch(\Exception $e)
		{
			sproutImport()->log($e->getMessage());

			craft()->userSession->setError($e->getMessage());
		}

		$this->redirectToPostedUrl();
	}
}
