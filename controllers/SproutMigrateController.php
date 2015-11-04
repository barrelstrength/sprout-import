<?php
namespace Craft;

class SproutMigrateController extends BaseController
{

	protected $allowAnonymous = array('actionEnqueueTasksByPost');

	public function actionEnqueueTasks()
	{
		$this->requirePostRequest();

		$tasks = array();
		$files = UploadedFile::getInstancesByName('files');

		foreach ($files as $file)
		{
			if (!$file->getHasError() && $file->getType() == 'application/json'
			|| $file->getType() == 'application/octet-stream')
			{
				$path = craft()->path->getPluginsPath().'sproutmigrate/downloads/'.$file->getName();

				if (move_uploaded_file($file->getTempName(), $path))
				{
					$tasks[] = $path;
				}
			}
		}

		try
		{
			sproutMigrate()->enqueueTasks($tasks);

			craft()->userSession->setNotice(Craft::t('({tasks}) Tasks queued successfully.', array('tasks' => count($tasks))));
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

		// support serialize format
		if(sproutMigrate()->isSerialized($elements))
		{
			$elements = unserialize($elements);

		}

		// Divide array for the tasks service
		$tasks = sproutMigrate()->sectionArray($elements, 10);

		sproutMigrate()->enqueueTasksByPost($tasks);

		try
		{
			craft()->userSession->setNotice(Craft::t('({tasks}) Tasks queued successfully.', array('tasks' => count($tasks))));
		}
		catch(\Exception $e)
		{
			craft()->userSession->setError($e->getMessage());
		}

		craft()->end();
	}

}
