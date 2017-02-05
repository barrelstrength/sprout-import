<?php

namespace Craft;

class SproutImport_TasksService extends BaseApplicationComponent
{
	/**
	 * Create the tasks that will import the user-provided data
	 *
	 * @param array $tasks
	 * @param bool  $seed
	 * @param array $type
	 *
	 * @return TaskModel
	 * @throws Exception
	 */
	public function createImportTasks(array $tasks, $seed = false, array $type)
	{
		if (!count($tasks))
		{
			throw new Exception(Craft::t('Unable to create import task. No tasks found.'));
		}

		return craft()->tasks->createTask('SproutImport_Import', Craft::t("Importing data."), array(
			'files' => $tasks,
			'seed'  => $seed,
			'type'  => $type
		));
	}

	/**
	 * Create the tasks that will import the Seed Data
	 *
	 * @param array $tasks
	 * @param array $type
	 *
	 * @return TaskModel
	 * @throws Exception
	 */
	public function createSeedTasks(array $tasks, array $type)
	{
		if (!count($tasks))
		{
			throw new Exception(Craft::t('Unable to create import task. No tasks found.'));
		}

		return craft()->tasks->createTask('SproutImport_Seed', Craft::t("Seeding data."), array(
			'seeds' => $tasks,
			'type'  => $type
		));
	}

	/**
	 * Create the tasks that will import our posted data
	 *
	 * @param $elements
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function createImportTasksFromPost($elements, $step = 10)
	{
		// support serialize format
		if ($this->isSerialized($elements))
		{
			$elements = unserialize($elements);
		}

		if (!is_array($elements))
		{
			$jsonContent = new SproutImport_JsonModel($elements);

			// Make sure we have JSON
			if ($jsonContent->hasErrors())
			{
				craft()->userSession->setError($jsonContent->getError('json'));

				SproutImportPlugin::log($jsonContent->getError('json'));

				return false;
			}
			else
			{
				$elements = $jsonContent->json;
			}
		}

		// Divide array for the tasks service
		$tasks = $this->sectionArray($elements, $step);

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

			craft()->userSession->setNotice(Craft::t('({tasks}) Tasks queued successfully.', array(
				'tasks' => count($tasks)
			)));

			return $response;
		}
		catch (\Exception $e)
		{
			craft()->userSession->setError($e->getMessage());
		}
	}

	/**
	 * Divide array by sections
	 *
	 * @param $array
	 * @param $step
	 *
	 * @return array
	 */
	function sectionArray($array, $step)
	{
		$sectioned = array();

		$k = 0;
		for ($i = 0; $i < count($array); $i++)
		{
			if (!($i % $step))
			{
				$k++;
			}

			$sectioned[$k][] = $array[$i];
		}

		return array_values($sectioned);
	}

	/**
	 * @author     Chris Smith <code+php@chris.cs278.org>
	 * @copyright  Copyright (c) 2009 Chris Smith (http://www.cs278.org/)
	 * @license    http://sam.zoy.org/wtfpl/ WTFPL
	 *
	 * @param    string $value  Value to test for serialized form
	 * @param    mixed  $result Result of unserialize() of the $value
	 *
	 * @return    boolean      True if $value is serialized data, otherwise false
	 */
	function isSerialized($value, &$result = null)
	{
		// Bit of a give away this one
		if (!is_string($value))
		{
			return false;
		}
		// Serialized false, return true. unserialize() returns false on an
		// invalid string or it could return false if the string is serialized
		// false, eliminate that possibility.
		if ($value === 'b:0;')
		{
			$result = false;

			return true;
		}
		$length = strlen($value);
		$end    = '';
		switch ($value[0])
		{
			case 's':
				if ($value[$length - 2] !== '"')
				{
					return false;
				}
			case 'b':
			case 'i':
			case 'd':
				// This looks odd but it is quicker than isset()ing
				$end .= ';';
			case 'a':
			case 'O':
				$end .= '}';
				if ($value[1] !== ':')
				{
					return false;
				}
				switch ($value[2])
				{
					case 0:
					case 1:
					case 2:
					case 3:
					case 4:
					case 5:
					case 6:
					case 7:
					case 8:
					case 9:
						break;
					default:
						return false;
				}
			case 'N':
				$end .= ';';
				if ($value[$length - 1] !== $end[0])
				{
					return false;
				}
				break;
			default:
				return false;
		}
		if (($result = @unserialize($value)) === false)
		{
			$result = null;

			return false;
		}

		return true;
	}
}