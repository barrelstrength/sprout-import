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
	public function save(array $elements)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		foreach ($elements as $element)
		{
			$type       = $this->getValueByKey('type', $element);
			$model      = $this->getElementModel($this->getValueByKey('content.beforeSave', $element), $element);
			$content    = $this->getValueByKey('content', $element, array());
			$fields     = $this->getValueByKey('content.fields', $element, array());
			$related    = $this->getValueByKey('content.related', $element, array());
			$attributes = $this->getValueByKey('attributes', $element, array());

			$this->resolveRelationships($related, $fields);
			$model->setAttributes($attributes);
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
				}
				catch (\Exception $e)
				{
					sproutMigrate()->error($e->getMessage());
				}
			}
			else
			{
				sproutMigrate()->error('Unable to validate.', $model->getErrors());
			}
		}

		if ($transaction && $transaction->active)
		{
			$transaction->commit();
		}

		$result = array(
			'saved'   => count($this->savedElements),
			'updated' => count($this->updatedElements),
			'unsaved' => count($this->unsavedElements),
		);

		return $result;
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
		$name  = $this->getModelClass($this->getValueByKey('type', $data));
		$model = new $name();

		if ($beforeSave)
		{
			$match = $this->getValueByKey('matchBy', $beforeSave);

			if ($match && ($value = $this->getValueByKey('matchValue', $beforeSave)))
			{
				$criteria = array('limit' => 1, $match => $value);

				try
				{
					$element = craft()->elements->getCriteria($this->getValueByKey('type', $data))->first($criteria);
				}
				catch (\Exception $e)
				{
					sproutMigrate()->error($e->getMessage());
				}

				if ($element)
				{
					$this->updatedElements[] = $element->getTitle();

					return $element;
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
			sproutMigrate()->log('Resolving relationships.', $related);

			foreach ($related as $name => $definition)
			{
				$type       = $this->getValueByKey('type', $definition);
				$matchBy    = $this->getValueByKey('matchBy', $definition);
				$matchValue = $this->getValueByKey('matchValue', $definition);
				$fieldType  = $this->getValueByKey('destinationField.type', $definition);

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
					$criteria = array('limit' => 1, $matchBy => $reference);

					try
					{
						$element = craft()->elements->getCriteria($type)->first($criteria);
					}
					catch (\Exception $e)
					{
						sproutMigrate()->error($e->getMessage());

						continue;
					}

					if ($element)
					{
						$ids[] = $element->getAttribute('id');
					}
				}

				if ($ids)
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
						else
						{
							unset($fields[$name]);
						}
					}
					else
					{
						$fields[$name] = $ids;
					}
				}
				else
				{
					unset($fields[$name]);
				}
			}
		}
	}

	/**
	 * @param string $key
	 * @param array  $data
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public function getValueByKey($key, array $data, $default = null)
	{
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

		SproutMigratePlugin::log(PHP_EOL.$message.PHP_EOL.PHP_EOL.$data, $level);
	}

	public function error($message, $data = null, $level = LogLevel::Error)
	{
		$this->log($message, $data, $level);
	}
}
