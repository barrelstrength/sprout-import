<?php
namespace Craft;

class SproutMigrateService extends BaseApplicationComponent
{
	/**
	 * Maps the element we support importing into
	 *
	 * @note Custom elements can be registered by defining sproutMigrateRegisterElements() in your plugin
	 * @var array
	 */
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

	/**
	 * Elements saved during the length of the import job
	 *
	 * @var array
	 */
	protected $savedElements = array();

	/**
	 * @var array
	 */
	protected $unsavedElements = array();

	/**
	 * @var array
	 */
	protected $updatedElements = array();

	/**
	 * Gives third party plugins a chance to register custom elements to import into
	 */
	public function init()
	{
		$results = craft()->plugins->call('sproutMigrateRegisterElements');

		if ($results)
		{
			foreach ($results as $elements)
			{
				if (is_array($elements) && count($elements))
				{
					foreach ($elements as $type => $element)
					{
						// @todo - Add validation and enforce rules
						if (isset($element['model']) && isset($element['method']) && isset($element['service']))
						{
							// @todo - Think about adding checks to replace isset() checks
							$this->mapping[$type] = $element;
						}
					}
				}
			}
		}
	}

	/**
	 * @param array $tasks
	 *
	 * @throws Exception
	 * @return TaskModel
	 */
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
	 * @param bool  $returnSavedElementIds
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
			$content    = $this->getValueByKey('content', $element);
			$fields     = $this->getValueByKey('content.fields', $element);
			$related    = $this->getValueByKey('content.related', $element);
			$attributes = $this->getValueByKey('attributes', $element);

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

					// @todo Ask Dale why the following is necessary
					// Assign user to created groups
					if (strtolower($type) == 'user' && !empty($attributes['groupId']))
					{
						$groupIds = $attributes['groupId'];
						craft()->userGroups->assignUserToGroups($model->id, $groupIds);
					}

				}
				catch (\Exception $e)
				{
					$this->unsavedElements[] = array('title' => $model->getTitle(), 'error' => $e->getMessage());
					$title = $this->getValueByKey('content.title', $element);
					$msg = $title . ' ' . implode(', ',array_keys($fields)) . ' Check field values if it exists.';
					sproutMigrate()->error($msg);
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

	/**
	 * Allows us to resolve relationships at the matrix field level
	 *
	 * @param $fields
	 */
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
				$criteria = craft()->elements->getCriteria($type);

				// The following is critical to import/relate elements not enabled
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

	/**
	 * @param $type
	 *
	 * @return mixed
	 */
	protected function getModelClass($type)
	{
		$type = strtolower($type);

		return $this->getValueByKey("{$type}.model", $this->mapping);
	}

	/**
	 * @param $type
	 *
	 * @return mixed
	 */
	protected function getServiceName($type)
	{
		$type = strtolower($type);

		return $this->getValueByKey("{$type}.service", $this->mapping);
	}

	/**
	 * @param $type
	 *
	 * @return mixed
	 */
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
						$foundAll = true;
						$element  = $criteria->first($attributes);

						if (strtolower($type) == 'asset' && !$element)
						{
							sproutMigrate()->log('Missing > '.$matchBy.': '.$reference);
						}

						if ($element)
						{
							$ids[] = $element->getAttribute('id');
						}
						else
						{
							$foundAll = false;
						}

						if (!$foundAll && $createIfNotFound && is_array($newElementDefinitions) && count($newElementDefinitions))
						{
							// Do we need to create the element?
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

	/**
	 * @param string|mixed $message
	 * @param array|mixed  $data
	 * @param string       $level
	 */
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

	/**
	 * @param        $message
	 * @param null   $data
	 * @param string $level
	 */
	public function error($message, $data = null, $level = LogLevel::Error)
	{
		$this->log($message, $data, $level);
	}
}
