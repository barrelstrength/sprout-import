<?php

namespace barrelstrength\sproutimport\importers\settings;

use barrelstrength\sproutbase\app\import\base\SettingsImporter;
use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutimport\SproutImport;
use Craft;
use craft\models\Section as SectionModel;
use craft\models\Section_SiteSettings;
use craft\models\Site;
use craft\records\Section as SectionRecord;

class Section extends SettingsImporter
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
        return 'Section';
    }

    /**
     * @return string
     */
    public function getModelName()
    {
        return SectionModel::class;
    }

    /**
     * @param null $handle
     *
     * @return SectionModel|null
     */
    public function getModelByHandle($handle = null)
    {
        return Craft::$app->getSections()->getSectionByHandle($handle);
    }

    public function getRecord()
    {
        return new SectionRecord();
    }

    /**
     * @return bool
     * @throws \Throwable
     * @throws \craft\errors\SectionNotFoundException
     */
    public function save()
    {
        $this->isNewSection = $this->model->id ? false : true;

        return Craft::$app->getSections()->saveSection($this->model);
    }

    /**
     * @param $id
     *
     * @return bool|mixed
     * @throws \Throwable
     */
    public function deleteById($id)
    {
        return Craft::$app->getSections()->deleteSectionById($id);
    }

    /**
     * @param SectionModel $section
     * @param array        $settings
     *
     * @return mixed|void
     */
    public function setModel($section, array $settings = [])
    {
        if (isset($settings['sectionId'])) {
            $section->id = $settings['sectionId'];
        }

        $section->name = $settings['name'] ?? null;
        $section->handle = $settings['handle'] ?? null;
        $section->type = $settings['type'] ?? null;
        $section->enableVersioning = $settings['enableVersioning'] ?? true;

        if ($section->type === SectionModel::TYPE_STRUCTURE && isset($settings['maxLevels'])) {
            $section->maxLevels = $settings['maxLevels'];
        }

        // Site-specific settings
        $allSiteSettings = [];

        /**
         * @var Site
         */
        foreach (Craft::$app->getSites()->getAllSites() as $site) {

            $siteSettings = new Section_SiteSettings();
            $siteSettings->siteId = $site->id;
            $siteSettings->enabledByDefault = 0;

            // If we don't have a matching Site in our import, skip this site
            if (!isset($settings['sites'][$site->handle])) {
                continue;
            }

            $postedSettings = $settings['sites'][$site->handle];

            if ($section->type === SectionModel::TYPE_SINGLE) {
                $siteSettings->hasUrls = true;
                $siteSettings->uriFormat = $postedSettings['singleUri'] ?: '__home__';
                $siteSettings->template = $postedSettings['template'];
            } else {
                $siteSettings->hasUrls = !empty($postedSettings['uriFormat']);

                if ($siteSettings->hasUrls) {
                    $siteSettings->uriFormat = $postedSettings['uriFormat'];
                    $siteSettings->template = $postedSettings['template'];
                } else {
                    $siteSettings->uriFormat = null;
                    $siteSettings->template = null;
                }

                $siteSettings->enabledByDefault = (bool)$postedSettings['enabledByDefault'];
            }

            $allSiteSettings[$site->id] = $siteSettings;
        }


        if (!empty($allSiteSettings)) {
            $section->setSiteSettings($allSiteSettings);
        }

        $this->model = $section;
    }

    /**
     * @param SectionModel $section
     * @param              $settings
     *
     * @return bool
     * @throws \Throwable
     */
    public function resolveNestedSettings($section, $settings)
    {
        // Check to see if we have any Entry Types we should also save
        if (!isset($settings['entryTypes']) OR empty($settings['entryTypes'])) {
            return true;
        }

        if ($section->id === null) {
            return true;
        }

        $sectionId = $section->id;

        // If we have a new section, we may want to update the Default Entry Type
        // that Craft creates when a section is created
        if ($this->isNewSection) {
            $entryTypes = $section->getEntryTypes();

            $firstEntryType = $entryTypes[0];
            $firstEntryTypeFields = $firstEntryType->getFieldLayout()->getFields();

            if (count($entryTypes) && empty($firstEntryTypeFields)) {
                $entryTypeId = $firstEntryType->id;

                Craft::$app->getSections()->deleteEntryTypeById($entryTypeId);
            }
        }

        // Add our new sectionId to our Entry Type settings
        foreach ($settings['entryTypes'] as $key => $entryTypeSettings) {
            $settings['entryTypes'][$key]['sectionId'] = $sectionId;

            $typeModel = SproutBase::$app->importers->getImporter($settings['entryTypes'][$key]);

            SproutImport::$app->settingsImporter->saveSetting($settings['entryTypes'][$key], $typeModel);
        }

        return true;
    }

    /**
     * @param $settings
     *
     * @return bool
     */
    protected function isHomepage($settings)
    {
        if ($settings['type'] != SectionModel::TYPE_SINGLE) {
            return false;
        }

        if (isset($settings['urlFormat']) && ($settings['urlFormat'] == '__home__')) {
            return true;
        }

        return false;
    }
}
