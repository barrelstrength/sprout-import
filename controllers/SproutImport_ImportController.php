<?php
namespace Craft;

class SproutImport_ImportController extends BaseController
{
	/**
	 * Import Content and Settings via JSON schema using the proper Craft Import Format
	 */
	public function actionRunImport()
	{
		$this->requirePostRequest();

		$tasks            = array();
		$seed             = craft()->request->getPost('seed');
		$pastedJsonString = craft()->request->getPost('pastedJson');
		$files            = UploadedFile::getInstancesByName('files');

		$count = 0;

		if (count($files))
		{
			$folderPath = sproutImport()->createTempFolder();

			foreach ($files as $file)
			{
				if (!$file->getHasError())
				{
					$fileContent = file_get_contents($file->getTempName());
					$jsonContent = new SproutImport_JsonModel($fileContent);

					// Make sure we have JSON
					if ($jsonContent->hasErrors())
					{
						craft()->userSession->setError($jsonContent->getError('json'));

						SproutImportPlugin::log($jsonContent->getError('json'));

						break;
					}

					$path = $folderPath . $file->getName();

					if (move_uploaded_file($file->getTempName(), $path))
					{
						$content = file_get_contents($path);

						$tasks[$count]['path']    = $path;
						$tasks[$count]['content'] = $content;
					}
				}
				else
				{
					craft()->userSession->setError($file->getError());

					SproutImportPlugin::log($file->getError());
				}

				$count++;
			}
		}

		if ($pastedJsonString)
		{
			$pastedJson = new SproutImport_JsonModel($pastedJsonString);

			// Make sure we have JSON
			if (!$pastedJson->hasErrors())
			{
				$tasks[$count]['path']    = 'pastedJson';
				$tasks[$count]['content'] = $pastedJsonString;
			}
			else
			{
				$message = $pastedJson->getError('json');

				craft()->userSession->setError($message);

				SproutImportPlugin::log($pastedJson->getError('json'));

				craft()->urlManager->setRouteVariables(array(
					'pastedJson' => $pastedJsonString
				));
			}
		}

		if (count($tasks))
		{
			try
			{
				sproutImport()->tasks->createImportTasks($tasks, $seed);

				craft()->userSession->setNotice(Craft::t('Files queued for import. Total: {tasks}', array(
					'tasks' => count($tasks)
				)));

				$this->redirectToPostedUrl();
			}
			catch (\Exception $e)
			{
				craft()->userSession->setError($e->getMessage());

				SproutImportPlugin::log($e->getMessage());
			}
		}
	}

	/**
	 * Import element content from a post request
	 *
	 * @todo - make this import method behave just like the standard actionRunImport method
	 */
	public function actionRunImportFromPost()
	{
		$elements = craft()->request->getPost('elements');

		sproutImport()->tasks->createImportTasksFromPost($elements);

		craft()->end();
	}
}
