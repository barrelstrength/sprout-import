<?php

namespace barrelstrength\sproutimport\importers\elements;

use barrelstrength\sproutimport\models\jobs\SeedJob;
use barrelstrength\sproutimport\SproutImport;
use Craft;
use barrelstrength\sproutbase\app\import\base\ElementImporter;
use craft\base\Field;
use craft\elements\Entry as EntryElement;
use craft\helpers\DateTimeHelper;
use craft\records\EntryVersion;

class Entry extends ElementImporter
{
    private $entryTypes;

    /**
     * @return mixed
     */
    public function getModelName()
    {
        return EntryElement::class;
    }

    /**
     * @return bool
     */
    public function hasSeedGenerator()
    {
        return true;
    }

    /**
     * @param SeedJob $seedJob
     *
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getSeedSettingsHtml(SeedJob $seedJob)
    {
        $sections = [
            'channel' => 'Channel'
        ];

        $channels = $this->getChannelSections();

        return Craft::$app->getView()->renderTemplate('sprout-base-import/_components/importers/elements/seed-generators/Entry/settings', [
            'id' => get_class($this),
            'sections' => $sections,
            'channels' => $channels,
            'seedJob' => $seedJob
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSeedSettingsErrors($settings)
    {
        if (isset($settings['channel']) && empty($settings['channel'])) {
            return Craft::t('sprout-import', 'Section is required.');
        }

        return null;
    }

    /**
     * Generate mock data for a Channel or Structure.
     *
     * Singles are not supported.
     *
     * @param $quantity
     * @param $settings
     *
     * @return array
     */
    public function getMockData($quantity, $settings)
    {
        $data = [];
        $sectionHandle = $settings['channel'];

        $section = Craft::$app->getSections()->getSectionByHandle($sectionHandle);
        $entryTypes = $section->getEntryTypes();

        $this->entryTypes = $entryTypes;

        $entryParams = [
            'sectionId' => $section->id,
            'sectionHandle' => $section->handle
        ];

        if (!empty($quantity)) {
            for ($i = 1; $i <= $quantity; $i++) {
                $entryId = null;

                if (!empty($entryTypes)) {
                    $randomEntryType = $entryTypes[array_rand($entryTypes)];

                    $entryParams['entryTypeId'] = $randomEntryType->id;

                    // Update entry prevent duplicate
                    if ($entryId != null) {
                        $entryParams['entryId'] = $entryId;
                    } else {
                        $entryParams['entryId'] = null;
                    }

                    $generatedEntry = $this->generateEntry($entryParams);

                    // Make sure authorId has int value.
                    $generatedEntry['attributes']['authorId'] = (int)$generatedEntry['attributes']['authorId'];

                    $data[] = $generatedEntry;
                }
            }
        }

        return $data;
    }

    /**
     * @param array $entryParams
     *
     * @return mixed
     */
    public function generateEntry(array $entryParams = [])
    {
        $fakerDate = $this->fakerService->dateTimeThisYear('now');

        $data = [];
        $data['@model'] = Entry::class;
        $data['attributes']['sectionId'] = $entryParams['sectionId'];
        $data['attributes']['typeId'] = $entryParams['entryTypeId'];

        $user = Craft::$app->getUser()->getIdentity();
        $data['attributes']['authorId'] = $user->id;
        $data['attributes']['postDate'] = $fakerDate;
        $data['attributes']['expiryDate'] = null;
        $data['attributes']['dateCreated'] = $fakerDate;
        $data['attributes']['dateUpdated'] = $fakerDate;
        $data['attributes']['enabled'] = true;

        $title = $entryParams['title'] ?? $this->fakerService->text(60);

        $data['content']['title'] = $title;

        $fieldLayouts = $this->getFieldLayouts();

        $data['content']['fields'] = SproutImport::$app->fieldImporter->getFieldsWithMockData($fieldLayouts);

        if (isset($entryParams['entryId'])) {
            $data['settings']['updateElement']['matchBy'] = 'id';
            $data['settings']['updateElement']['matchValue'] = $entryParams['entryId'];
            $data['settings']['updateElement']['matchCriteria'] = ['section' => $entryParams['sectionHandle']];
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getAllFieldHandles()
    {
        $elementType = get_class($this->model);

        $fields = Craft::$app->getFields()->getFieldsByElementType($elementType);

        $handles = [];
        if (!empty($fields)) {

            /**
             * @var $field Field
             */
            foreach ($fields as $field) {
                $handles[] = $field->handle;
            }
        }

        return $handles;
    }

    /**
     * @return array
     */
    private function getFieldLayouts()
    {
        $entryTypes = $this->entryTypes;

        $fieldLayouts = [];

        if (!empty($entryTypes)) {
            foreach ($entryTypes as $entryType) {
                $fieldLayoutId = $entryType->fieldLayoutId;

                $layouts = Craft::$app->getFields()->getFieldsByLayoutId($fieldLayoutId);
                // Always use array merge because $layouts variable returns an array
                $fieldLayouts = array_merge($fieldLayouts, $layouts);
            }
        }

        return $fieldLayouts;
    }

    /**
     * @param $entry
     *
     * @return int|null
     * @throws \yii\base\InvalidConfigException
     */
    public function getFieldLayoutId($entry)
    {
        /**
         * @var $entry EntryElement
         */
        return $entry->getType()->fieldLayoutId;
    }

    /**
     * @return array
     */
    public function getChannelSections()
    {
        $selects = [];
        $sections = Craft::$app->getSections()->getAllSections();
        if (!empty($sections)) {
            foreach ($sections as $section) {
                if ($section->type == 'single') {
                    continue;
                }
                $selects[$section->handle] = $section->name;
            }
        }

        return $selects;
    }

    public function getImporterDataKeys()
    {
        return ['enableVersioning'];
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function afterSaveElement()
    {
        $settings = $this->rows;

        /**
         * @var $entry EntryElement
         */
        $entry = $this->model;

        $revisionsService = Craft::$app->getEntryRevisions();

        $enableVersioning = $settings['enableVersioning'] ?? null;

        // Overrides section default settings
        if ($enableVersioning === false) {
            return null;
        }

        if ($enableVersioning === true || $entry->getSection()->enableVersioning) {
            $revisionsService->saveVersion($entry);
        }

        return null;
    }
}