<?php

namespace Craft;

class SproutImport_TasksService extends BaseApplicationComponent
{
	/**
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

		return craft()->tasks->createTask('SproutImport', Craft::t("Importing data."), array(
			'files' => $tasks,
			'seed'  => $seed
		));
	}

	/**
	 * @todo - merge with createImportTasks method?
	 *
	 * @param array $tasks
	 *
	 * @return TaskModel
	 * @throws Exception
	 */
	public function enqueueTasksByPost(array $tasks)
	{
		if (!count($tasks))
		{
			throw new Exception(Craft::t('No tasks to enqueue'));
		}

		$taskName    = Craft::t('Craft Migration');
		$description = Craft::t('Sprout Migrate By Post Request');

		return craft()->tasks->createTask('SproutImport_Post', Craft::t($description), array(
			'elements' => $tasks
		));
	}

	/**
	 * Migrate elements to Craft
	 *
	 * @param $elements
	 *
	 * @throws Exception
	 */
	public function setEnqueueTasksByPost($elements)
	{

		// support serialize format
		if (sproutImport()->isSerialized($elements))
		{
			$elements = unserialize($elements);
		}

		// Divide array for the tasks service
		$tasks = sproutImport()->sectionArray($elements, 10);

		$this->enqueueTasksByPost($tasks);

		try
		{
			craft()->userSession->setNotice(Craft::t('({tasks}) Tasks queued successfully.', array('tasks' => count($tasks))));
		}
		catch (\Exception $e)
		{
			craft()->userSession->setError($e->getMessage());
		}
	}
}