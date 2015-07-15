<?php
namespace Craft;

class SproutMigrateService extends BaseApplicationComponent
{
	protected $mapping = array(
		'user'     => array(
			'model'   => 'Craft\\UserModel',
			'method'  => 'saveUser',
			'service' => 'users',
		),
		'entry'    => array(
			'model'   => 'Craft\\EntryModel',
			'method'  => 'saveEntry',
			'service' => 'entries',
		),
		'category' => array(
			'model'   => 'Craft\\CategoryModel',
			'method'  => 'saveCategory',
			'service' => 'categories',
		),
		'tag'      => array(
			'model'   => 'Craft\\TagModel',
			'method'  => 'saveTag',
			'service' => 'tags',
		)
	);

	protected $savedElements = array();
	protected $unsavedElements = array();
	protected $updatedElements = array();

	public function enqueueTasks(array $tasks)
	{
		if (!count($tasks))
		{
			throw new Exception(Craft::t('No tasks to enqueue'));
		}

		$taskName    = craft()->request->getPost('taskName');
		$description = $tasks ? "Sprout Migrate {$taskName}" : 'Sprout Migrate';

		return craft()->tasks->createTask('SproutMigrate', Craft::t($description), array('files' => $tasks));
	}

	/**
	 * @param array $elements
	 *
	 * @return array
	 * @throws Exception
	 * @throws \CDbException
	 * @throws \Exception
	 */
	public function save(array $elements, $returnSavedElementIds = false)
	{
		$transaction     = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		$savedElementIds = array();

		foreach ($elements as $element)
		{
			$type       = $this->getValueByKey('type', $element);
			$model      = $this->getElementModel($this->getValueByKey('content.beforeSave', $element), $element);
			$content    = $this->getValueByKey('content', $element, array());
			$fields     = $this->getValueByKey('content.fields', $element, array());
			$related    = $this->getValueByKey('content.related', $element, array());
			$attributes = $this->getValueByKey('attributes', $element, array());

			$this->resolveMatrixRelationships($fields);
			$this->resolveRelationships($related, $fields);
			$model->setAttributes($attributes);

			unset($element['content']['related']);

			$model->setContent($content);
			$model->setContentFromPost($fields);

			if ($model->validate())
			{
				$isNewElement = !$model->id;

				try
				{
					$saved = craft()->{$this->getServiceName($type)}->{$this->getMethodName($type)}($model, $element);

					if ($saved && $isNewElement)
					{
						$savedElementIds[]     = $model->id;
						$this->savedElements[] = $model->getTitle();
					}
					elseif ($saved && !$isNewElement)
					{
						$this->updatedElements[] = $model->getTitle();
					}
					else
					{
						$this->unsavedElements[] = $model->getTitle();
					}
					// Assign user to created groups
					if(strtolower($type) == 'user' && !empty($attributes['groupId'])) {
						$groupIds = $attributes['groupId'];						
						craft()->userGroups->assignUserToGroups($model->id, $groupIds);
					}
					
				}
				catch (\Exception $e)
				{
					$this->unsavedElements[] = array('title' => $model->getTitle(), 'error' => $e->getMessage());

					sproutMigrate()->error($e->getMessage());
				}
			}
			else
			{
				$this->unsavedElements[] = array(
					'title' => $model->getTitle(),
					'error' => print_r($model->getErrors(), true)
				);

				sproutMigrate()->error('Unable to validate.', $model->getErrors());
			}
		}

		if ($transaction && $transaction->active)
		{
			$transaction->commit();
		}

		$result = array(
			'saved'          => count($this->savedElements),
			'updated'        => count($this->updatedElements),
			'unsaved'        => count($this->unsavedElements),
			'unsavedDetails' => $this->unsavedElements,
		);

		return $returnSavedElementIds ? $savedElementIds : $result;
	}

	public function resolveMatrixRelationships(&$fields)
	{
		foreach ($fields as $field => $blocks)
		{
			if (is_array($blocks) && count($blocks))
			{
				foreach ($blocks as $block => $attributes)
				{
					if (strpos($block, 'new') === 0 && isset($attributes['related']))
					{
						$blockFields   = isset($attributes['fields']) ? $attributes['fields'] : array();
						$relatedFields = $attributes['related'];

						$this->resolveRelationships($relatedFields, $blockFields);

						unset($fields[$field][$block]['related']);

						if (empty($blockFields))
						{
							unset($fields[$field][$block]);
						}
						else
						{
							$fields[$field][$block]['fields'] = array_unique($blockFields);
						}
					}
				}
			}
		}
	}

	/**
	 * @param null  $beforeSave
	 * @param array $data
	 *
	 * @return BaseElementModel|EntryModel|CategoryModel|UserModel
	 * @throws Exception
	 */
	protected function getElementModel($beforeSave = null, array $data = array())
	{
		$type  = $this->getValueByKey('type', $data);
		$name  = $this->getModelClass($type);
		$model = new $name();

		if ($beforeSave)
		{
			$matchBy       = $this->getValueByKey('matchBy', $beforeSave);
			$matchValue    = $this->getValueByKey('matchValue', $beforeSave);
			$matchCriteria = $this->getValueByKey('matchCriteria', $beforeSave);

			if ($matchBy && $matchValue)
			{
				$criteria         = craft()->elements->getCriteria($type);
				$criteria->status = null;

				if (array_key_exists($matchBy, $criteria->getAttributes()))
				{
					$attributes = array('limit' => 1, $matchBy => $matchValue);
				}
				else
				{
					$attributes = array('limit' => 1, 'search' => $matchBy.':'.$matchValue);
				}

				if (is_array($matchCriteria))
				{
					$attributes = array_merge($attributes, $matchCriteria);
				}

				try
				{
					$element = $criteria->first($attributes);

					if ($element)
					{
						return $element;
					}
				}
				catch (\Exception $e)
				{
					sproutMigrate()->error($e->getMessage());
				}
			}
		}

		return $model;
	}

	protected function getModelClass($type)
	{
		$type = strtolower($type);

		return $this->getValueByKey("{$type}.model", $this->mapping);
	}

	protected function getServiceName($type)
	{
		$type = strtolower($type);

		return $this->getValueByKey("{$type}.service", $this->mapping);
	}

	protected function getMethodName($type)
	{
		$type = strtolower($type);

		return $this->getValueByKey("{$type}.method", $this->mapping);
	}

	/**
	 * @param array $related
	 * @param array $fields
	 *
	 * @throws Exception
	 */
	protected function resolveRelationships(array $related = null, array &$fields)
	{
		if (count($related))
		{
			foreach ($related as $name => $definition)
			{
				if (empty($definition))
				{
					unset($related[$name]);
					continue;
				}

				$type                  = $this->getValueByKey('type', $definition);
				$matchBy               = $this->getValueByKey('matchBy', $definition);
				$matchValue            = $this->getValueByKey('matchValue', $definition);
				$matchCriteria         = $this->getValueByKey('matchCriteria', $definition);
				$fieldType             = $this->getValueByKey('destinationField.type', $definition);
				$createIfNotFound      = $this->getValueByKey('createIfNotFound', $definition);
				$newElementDefinitions = $this->getValueByKey('newElementDefinitions', $definition);

				if (!$type && !$matchValue && !$matchBy)
				{
					continue;
				}

				if (!is_array($matchValue))
				{
					$matchValue = ArrayHelper::stringToArray($matchValue);
				}

				if (!count($matchValue))
				{
					continue;
				}

				$ids = array();

				foreach ($matchValue as $reference)
				{
					$criteria         = craft()->elements->getCriteria($type);
					$criteria->status = null;

					if (array_key_exists($matchBy, $criteria->getAttributes()))
					{
						$attributes = array('limit' => 1, $matchBy => $reference);
					}
					else
					{
						$attributes = array('limit' => 1, 'search' => $matchBy.':'.$reference);
					}

					if (is_array($matchCriteria))
					{
						$attributes = array_merge($attributes, $matchCriteria);
					}

					try
					{
						$element = $criteria->first($attributes);

						if ($element)
						{
							$ids[] = $element->getAttribute('id');
						}
						elseif ($createIfNotFound && is_array($newElementDefinitions) && count($newElementDefinitions))
						{
							// Do we need to create the element?
							// @todo - Make sure save does not get called on every iteration
							$ids = array_merge($ids, $this->save($newElementDefinitions, true));
						}
					}
					catch (\Exception $e)
					{
						sproutMigrate()->error($e->getMessage(), $e);

						continue;
					}
				}

				if (count($ids))
				{
					if (strtolower($fieldType) === 'matrix')
					{
						$blockType      = $this->getValueByKey('destinationField.blockType', $definition);
						$blockTypeField = $this->getValueByKey('destinationField.blockTypeField', $definition);

						if ($blockType && $blockTypeField)
						{
							$fields[$name] = array(
								'new1' => array(
									'type'    => $blockType,
									'enabled' => true,
									'fields'  => array(
										$blockTypeField => $ids
									)
								)
							);
						}
					}
					else
					{
						$fields[$name] = $ids;
					}
				}
			}
		}
	}

	/**
	 * @param string $key
	 * @param mixed  $data
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public function getValueByKey($key, $data, $default = null)
	{
		if (!is_array($data))
		{
			sproutMigrate()->error('getValueByKey() was passed in a non-array as data.');

			return $default;
		}

		if (!is_string($key) || empty($key) || !count($data))
		{
			return $default;
		}

		// @assert $key contains a dot notated string
		if (strpos($key, '.') !== false)
		{
			$keys = explode('.', $key);

			foreach ($keys as $innerKey)
			{
				if (!array_key_exists($innerKey, $data))
				{
					return $default;
				}

				$data = $data[$innerKey];
			}

			return $data;
		}

		return array_key_exists($key, $data) ? $data[$key] : $default;
	}

	public function log($message, $data = null, $level = LogLevel::Info)
	{
		if ($data)
		{
			$data = print_r($data, true);
		}

		if (!is_string($message))
		{
			$message = print_r($message, true);
		}

		SproutMigratePlugin::log(PHP_EOL.$message.PHP_EOL.PHP_EOL.$data, $level);
	}

	public function error($message, $data = null, $level = LogLevel::Error)
	{
		$this->log($message, $data, $level);
	}
}
