<?php
namespace Craft;

class SectionSproutImportImporter extends BaseSproutImportImporter
{
	public $isNewSection;

	/**
	 * @return string
	 */
	public function defineModel()
	{
		return 'Craft\\SectionModel';
	}

	/**
	 * @param null $handle
	 *
	 * @return SectionModel|null
	 */
	public function getObjectByHandle($handle)
	{
		return craft()->sections->getSectionByHandle($handle);
	}

	/**
	 * @return bool
	 * @throws Exception
	 * @throws \Exception
	 */
	public function save()
	{
		$this->isNewSection = ($this->model->id) ? false : true;

		return craft()->sections->saveSection($this->model);
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function deleteById($id)
	{
		return craft()->sections->deleteSectionById($id);
	}

	/**
	 * @param $model
	 * @param $settings
	 */
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

	/**
	 * @param $model
	 * @param $settings
	 *
	 * @return bool
	 * @throws Exception
	 * @throws \Exception
	 */
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

			$firstEntryType       = $entryTypes[0];
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

			$entryType = sproutImport()->settings->saveSetting($settings['entryTypes'][$key]);
		}
	}
}
