<?php
namespace Craft;

class SproutMigrateController extends BaseController
{
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

	public function actionEnqueueSettingsTasks()
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

		// ---------------
		// THIS CODE GOES IN THE TASK FILE
		// only here for convenience...  Update $path => $file in the Task file and uncomment the delete file line.

		if ($content = sproutMigrate()->getJson($path))
		{
			if ($content = sproutMigrate()->getJson($path))
			{
				// @TODO - make logic around parsing settings more robust
				$settings = $content['@settings'];

				try
				{
					// @TODO - add control for $trackSeeds via setting
					$result = sproutMigrate()->saveSettings($settings, true);

					//IOHelper::deleteFile($file);

					sproutMigrate()->log('Task result for ' . $file, $result);

					return true;
				} catch (\Exception $e)
				{
					sproutMigrate()->error($e->getMessage());
				}
			}
			else
			{
				sproutMigrate()->error('Unable to parse file.', compact('file'));
			}

			return false;
		}

		// ---------------

		//try
		//{
		//	sproutMigrate()->enqueueSettingsTasks($tasks);
		//
		//	craft()->userSession->setNotice(Craft::t('({tasks}) Tasks queued successfully.', array('tasks' => count($tasks))));
		//}
		//catch(\Exception $e)
		//{
		//	craft()->userSession->setError($e->getMessage());
		//}

		$this->redirectToPostedUrl();
	}
}
