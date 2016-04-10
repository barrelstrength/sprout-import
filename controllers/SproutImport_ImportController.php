<?php
namespace Craft;

class SproutImportController extends BaseController
{
	/**
	 * Import Elements
	 *
	 * @throws HttpException
	 */
	public function actionRunImport()
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
			sproutImport()->tasks->createImportTasks($tasks, $seed);

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

	/**
	 * Queue Posted Elements for import via a task
	 *
	 * @todo - should this method be updated to accept elements and settings?
	 */
	public function actionEnqueueTasksByPost()
	{
		$elements = craft()->request->getPost('elements');

		sproutImport()->tasks->setEnqueueTasksByPost($elements);
		craft()->end();
	}
}
