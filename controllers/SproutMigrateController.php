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

	public function actionEnqueueTasksByPost()
	{
		//$data = craft()->request->getPost('elements');
		$elements = $this->getTempData();

		Craft::dump(sproutMigrate()->sectionArray($elements,5));
		//sproutMigrate()->save($elements);
		craft()->end();
	}

	private function getTempData()
	{
		$elements = array();
		$elements[0]['type']       = "Entry";
		$elements[0]['attributes'] = array(
			'sectionId'	  => 2,
			'typeId'	  => 2,
			'locale'	  => 'en_us',
			'authorId'	  => 1,
			'slug'	      => 'rocket-art-from-thailand',
			'postDate'    => "2015-02-27 16:43:52",
			'expiryDate'  => null,
			"dateCreated" => "2015-02-27 16:43:52",
			"dateUpdated" => "2015-02-27 16:51:42",
			"enabled"     => true
		);

		$elements[0]['content']	= array(
			"oldId"	 => 266,
			"title"  => "Rocket-Art from Thailand",
			"fields" => array(
				'body' => 'this is the body one'
			),
			"related"	 => null,
			"beforeSave" => array(
				"matchBy"        => "oldId",
				"matchValue"     => 266,
				"matchCriteria"  => array(
					"section" => "news"
				)
			)
		);

		for($i = 1; $i < 20; $i++)
		{
			$elements[$i]['type'] = 'element ' . $i;
		}
		return $elements;
	}
}
