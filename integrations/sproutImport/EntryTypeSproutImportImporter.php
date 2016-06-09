<?php
namespace Craft;

class EntryTypeSproutImportImporter extends BaseSproutImportImporter
{
	/**
	 * @return string
	 */
	public function defineModel()
	{
		return 'EntryTypeModel';
	}

	/**
	 * @param $model
	 * @param $settings
	 */
	public function populateModel($entryType, $entryTypeSettings)
	{
		$entryType->setAttributes($entryTypeSettings);

		// @TODO - make fieldContext and contentTable dynamic
		craft()->content->fieldContext = 'global';
		// craft()->content->contentTable = 'content';

		//------------------------------------------------------------

		// Do we have a new field that doesn't exist yet?
		// If so, save it and grab the id.

		if (isset($entryTypeSettings['fieldLayout']))
		{
			$fieldLayoutTabs = $entryTypeSettings['fieldLayout'];
			$fieldLayout = array();
			$requiredFields = array();

			foreach ($fieldLayoutTabs as $tab)
			{
				$tabName = $tab['name'];
				$fields = $tab['fields'];

				foreach ($fields as $fieldSettings)
				{
					$field = sproutImport()->setting->saveSetting($fieldSettings);

					$fieldLayout[$tabName][] = $field->id;

					if ($field->required)
					{
						$requiredFields[] = $field->id;
					}
				}
			}

			// @TODO - move this to a different place to save?

			// Set the field layout
			$fieldLayout = craft()->fields->assembleLayout($fieldLayout, $requiredFields);

			// @todo FieldLayout Type should be dynamic
			$fieldLayout->type = 'Entry';

			// @todo - get the parent SECTION (or Field Layout Container and resave things...)
			// Should I be using the MODEL or the JSON Settings?
			// How do I know?  Hrmm....
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
}
