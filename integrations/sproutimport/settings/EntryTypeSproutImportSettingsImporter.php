<?php
namespace Craft;

class EntryTypeSproutImportSettingsImporter extends BaseSproutImportSettingsImporter
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return "Entry Type";
	}

	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'EntryType';
	}

	/**
	 * @param $model
	 * @param $settings
	 */
	public function setModel($entryType, $entryTypeSettings)
	{
		$entryType->setAttributes($entryTypeSettings);

		// @TODO - make fieldContext and contentTable dynamic
		craft()->content->fieldContext = 'global';
		// craft()->content->contentTable = 'content';

		//------------------------------------------------------------

		if (isset($entryTypeSettings['fieldLayout']))
		{
			$fieldLayoutTabs = $entryTypeSettings['fieldLayout'];
			$fieldLayout     = array();
			$requiredFields  = array();

			foreach ($fieldLayoutTabs as $tab)
			{
				$tabName = $tab['name'];
				$fields  = $tab['fields'];

				foreach ($fields as $fieldSettings)
				{
					$field = sproutImport()->settingsImporter->saveSetting($fieldSettings);

					$fieldLayout[$tabName][] = $field->id;

					if ($field->required)
					{
						$requiredFields[] = $field->id;
					}
				}
			}

			if ($entryType->getFieldLayout() != null)
			{
				// Remove previous field layout and update layout
				craft()->fields->deleteLayoutById($entryType->fieldLayoutId);
			}

			$fieldLayout = craft()->fields->assembleLayout($fieldLayout, $requiredFields);

			$fieldLayout->type = 'Entry';

			$entryType->setFieldLayout($fieldLayout);
		}

		$this->model = $entryType;
	}

	/**
	 * @return bool
	 * @throws Exception
	 * @throws \Exception
	 */
	public function save()
	{
		return craft()->sections->saveEntryType($this->model);
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function deleteById($id)
	{
		return craft()->sections->deleteEntryTypeById($id);
	}

	public function getModelByHandle($handle = null)
	{
		$types = craft()->sections->getEntryTypesByHandle($handle);

		if (!empty($types))
		{
			return $types[0];
		}
	}
}
