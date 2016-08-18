<?php
namespace Craft;

class FieldSproutImportSettingsImporter extends BaseSproutImportSettingsImporter
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return "Field";
	}

	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'Field';
	}

	/**
	 * @param null $handle
	 *
	 * @return FieldModel|null
	 */
	public function getModelByHandle($handle = null)
	{
		return craft()->fields->getFieldByHandle($handle);
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function save()
	{
		try
		{
			$type = $this->model['type'];
			$field = craft()->fields->getFieldType($type);

			if ($field != null)
			{
				if ($this->model->settings == null)
				{
					$defaultSettings = $field->getSettings()->getAttributes();

					// Save default settings if no settings are provided
					$this->model->settings = $defaultSettings;
				}

				// Create a Field Group if one isn't identified
				if (!$this->model->groupId)
				{
					$defaultFieldGroupId = $this->getDefaultFieldGroup();
					$this->model->groupId = $defaultFieldGroupId;
				}

				return craft()->fields->saveField($this->model);
			}
			else
			{
				$message = $type . ' field importer not supported';

				SproutImportPlugin::log($message, LogLevel::Error);

				sproutImport()->addError($message, 'field-importer-null');

				return false;
			}
		}
		catch (\Exception $e)
		{
			SproutImportPlugin::log($e->getMessage(), LogLevel::Error);

			sproutImport()->addError($e->getMessage(), 'field-importer-error');

			return false;
		}

	}

	/**
	 * @param $id
	 *
	 * @return bool
	 */
	public function deleteById($id)
	{
		return craft()->fields->deleteFieldById($id);
	}

	/**
	 * @return mixed|null
	 */
	protected function getDefaultFieldGroup()
	{
		// @todo - cache this somewhere so we don't query the db
		// for every field that doesn't have a fieldGroup
		$groups = craft()->db->createCommand()
			->select('*')
			->from('fieldgroups')
			->where('name = :name', array(':name' => 'Default'))
			->orWhere('name = :name2', array(':name2' => 'Sprout Import'))
			->queryAll();

		$groupId = null;

		foreach ($groups as $group)
		{
			if ($group['name'] == 'Default')
			{
				return $group['id'];
			}
		}

		if (!$groupId)
		{
			SproutImportPlugin::log('No field group exists. Creating the Sprout Import field group.');

			$groupId = $this->createSproutImportFieldGroup();
		}

		return $groupId;
	}

	/**
	 * @return mixed
	 */
	protected function createSproutImportFieldGroup()
	{
		$group = new FieldGroupModel();
		$group->name = 'Sprout Import';

		if (craft()->fields->saveGroup($group))
		{
			SproutImportPlugin::log('Sprout Import field group created successfully.');
		}
		else
		{
			SproutImportPlugin::log('Could not save the Sprout Import field group.', LogLevel::Warning);
		}

		return $group->id;
	}
}
