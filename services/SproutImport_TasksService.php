<?php

namespace Craft;

class SproutImport_TasksService extends BaseApplicationComponent
{
	/**
	 * Create the tasks that will import our user-provided data
	 *
	 * @param array $tasks
	 *
	 * @throws Exception
	 * @return TaskModel
	 */
	public function createImportTasks(array $tasks, $seed = false)
	{
		if (!count($tasks))
		{
			throw new Exception(Craft::t('Unable to create import task. No tasks found.'));
		}

		return craft()->tasks->createTask('SproutImport_Import', Craft::t("Importing data."), array(
			'files' => $tasks,
			'seed'  => $seed
		));
	}

	/**
	 * Create the tasks that will import our posted data
	 *
	 * @param $elements
	 *
	 * @throws Exception
	 */
	public function createImportTasksFromPost($elements)
	{
		// support serialize format
		if (sproutImport()->isSerialized($elements))
		{
			$elements = unserialize($elements);
		}

		// Divide array for the tasks service
		$tasks = sproutImport()->sectionArray($elements, 10);

		if (!count($tasks))
		{
			throw new Exception(Craft::t('Unable to create import task. No tasks found.'));
		}

		try
		{
			$taskName = Craft::t("Importing data from post.");

			$response = craft()->tasks->createTask('SproutImport_ImportFromPost', $taskName, array(
				'elements' => $tasks
			));

			craft()->userSession->setNotice(Craft::t('({tasks}) Tasks queued successfully.', array('tasks' => count($tasks))));

			return $response;
		}
		catch (\Exception $e)
		{
			craft()->userSession->setError($e->getMessage());
		}
	}
}