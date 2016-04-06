<?php
namespace Craft;

class SectionSproutImportImporter extends SproutImportBaseImporter
{
	public $isNewSection;

	public function getObjectByHandle($handle)
	{
		return craft()->sections->getSectionByHandle($handle);
	}

	public function defineModel()
	{
		return 'Craft\\SectionModel';
	}

	public function populateModel($model, $settings)
	{
		if (!isset($settings['urlFormat']))
		{
			return;
		}

		$locales = array();

		if (empty($settings['locales']))
		{
			$primaryLocaleId = craft()->i18n->getPrimarySiteLocaleId();
			$localeIds       = array($primaryLocaleId);

			// @TODO - homepage check is hard coded
			$isHomepage = false;
			// $isHomepage = ($section->type == SectionType::Single && craft()->request->getPost('types.'.$section->type.'.homepage'));

			foreach ($localeIds as $localeId)
			{
				if ($isHomepage)
				{
					$settings['urlFormat'] = '__home__';
					$nestedUrlFormat       = null;
				}
				else
				{
					$urlFormat = (isset($settings['urlFormat'][$localeId])) ? $settings['urlFormat'][$localeId] : $settings['urlFormat'];

					// @TODO - improve this, hard coded fake data
					$nestedUrlFormat = 'NOT WORKING';
					// $settings['urlFormat'][$settings['type']]['nestedUrlFormat'][$localeId];
					// craft()->request->getPost('types.'.$section->type.'.nestedUrlFormat.'.$localeId);
					// 
				}

				$locales[$localeId] = new SectionLocaleModel(array(
					'locale'           => $localeId,
					// @TODO - improve this, hard coded
					// 'enabledByDefault' => (bool) craft()->request->getPost('defaultLocaleStatuses.'.$localeId),
					'enabledByDefault' => true,
					'urlFormat'        => $urlFormat,
					'nestedUrlFormat'  => $nestedUrlFormat,
				));
			}

			$model->setLocales($locales);
		}

		// Assign any setting values we can to the model
		$model->setAttributes($settings);

		$this->model = $model;
	}

	public function save()
	{
		$this->isNewSection = ($this->model->id) ? false : true;

		return craft()->sections->saveSection($this->model);
	}

	public function deleteById($id)
	{
		return craft()->sections->deleteSectionById($id);
	}

	public function resolveNestedSettings($model, $settings)
	{
		// Check to see if we have any Entry Types we should also save
		if (!isset($settings['entryTypes']) OR empty($settings['entryTypes']))
		{
			return true;
		}

		if (empty($model->id))
		{
			return true;
		}

		$sectionId = $model->id;

		// If we have a new section, we may want to update the Default Entry Type
		// that Craft creates when a section is created
		if ($this->isNewSection)
		{
			$entryTypes = $model->getEntryTypes();

			$firstEntryType = $entryTypes[0];
			$firstEntryTypeFields = $firstEntryType->getFieldLayout()->getFields();

			if (count($entryTypes) && empty($firstEntryTypeFields))
			{
				$entryTypeId = $firstEntryType->id;
				craft()->sections->deleteEntryTypeById($entryTypeId);
			}
		}

		// Add our new sectionId to our Entry Type settings
		foreach ($settings['entryTypes'] as $key => $entryTypeSettings)
		{
			$settings['entryTypes'][$key]['sectionId'] = $sectionId;

			$entryType = sproutImport()->setting->saveSetting($settings['entryTypes'][$key]);

			// ------------------------------------------------------------

			$entryTypeId   = $entryType->id;
			$fieldLayoutId = $entryType->fieldLayoutId;

			// @TODO - make fieldContext and contentTable dynamic
			craft()->content->fieldContext = 'global';
			// craft()->content->contentTable = 'content';

			//------------------------------------------------------------

			// Do we have a new field that doesn't exist yet?
			// If so, save it and grab the id.

			$fieldLayoutTabs = $entryTypeSettings['fieldLayout'];
			$fieldLayout     = array();
			$requiredFields  = array();


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

			craft()->sections->saveEntryType($entryType);
		}
	}
}
