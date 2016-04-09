<?php
namespace Craft;

class SproutImportController extends BaseController
{
	/**
	 * Import Elements
	 *
	 * @throws HttpException
	 */
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

	/**
	 * Queue Elements for import via a task
	 */
	public function actionEnqueueTasksByPost()
	{
		$elements = craft()->request->getPost('elements');

		sproutImport()->setEnqueueTasksByPost($elements);
		craft()->end();
	}
}
