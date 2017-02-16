<?php
namespace Craft;

class SectionSproutImportSettingsImporter extends BaseSproutImportSettingsImporter
{
	/**
	 * @var
	 */
	public $isNewSection;

	/**
	 * @return string
	 */
	public function getName()
	{
		return "Section";
	}

	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'Section';
	}

	/**
	 * @param null $handle
	 *
	 * @return SectionModel|null
	 */
	public function getModelByHandle($handle)
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
	public function setModel($model, $settings)
	{
		// Assign any Shared and Type-specific values we can to the model
		$model->setAttributes($settings);

		// Locale-specific attributes
		$locales = array();

		if (isset($settings['locales']) && count($settings['locales']))
		{
			$localeIds = $settings['locales'];
		}
		else
		{
			$primaryLocaleId = craft()->i18n->getPrimarySiteLocaleId();
			$localeIds       = array($primaryLocaleId);
		}

		$isHomepage = $this->isHomepage($settings);

		foreach ($localeIds as $localeId)
		{
			if ($isHomepage)
			{
				$urlFormat       = $settings['urlFormat'];
				$nestedUrlFormat = null;
			}
			else
			{
				$urlFormat       = (isset($settings['urlFormat'][$localeId])) ? $settings['urlFormat'][$localeId] : null;
				$nestedUrlFormat = (isset($settings['nestedUrlFormat'][$localeId])) ? $settings['nestedUrlFormat'][$localeId] : null;
			}

			$locales[$localeId] = new SectionLocaleModel(array(
				'locale'           => $localeId,
				'enabledByDefault' => (bool) isset($settings['defaultLocaleStatuses'][$localeId]) ? $settings['defaultLocaleStatuses'][$localeId] : false,
				'urlFormat'        => $urlFormat,
				'nestedUrlFormat'  => $nestedUrlFormat,
			));
		}

		$hasUrls = (isset($settings['hasUrls'])) ? $settings['hasUrls'] : false;

		if ($hasUrls == true && $urlFormat == null)
		{
			sproutImport()->addError(Craft::t('Invalid urlFormat value. E.g. {"en_us": "blog/{slug}"}'), 'section-urlFormat');
		}

		$model->setLocales($locales);

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

			$entryType = sproutImport()->settingsImporter->saveSetting($settings['entryTypes'][$key]);
		}
	}

	/**
	 * @param $settings
	 *
	 * @return bool
	 */
	protected function isHomepage($settings)
	{
		if ($settings['type'] != SectionType::Single)
		{
			return false;
		}

		if (isset($settings['urlFormat']) && ($settings['urlFormat'] == '__home__'))
		{
			return true;
		}

		return false;
	}
}
