<?php

namespace barrelstrength\sproutimport\services;

use barrelstrength\sproutbase\contracts\sproutimport\BaseFieldImporter;
use barrelstrength\sproutimport\SproutImport;
use craft\base\Component;
use craft\base\Element;
use Craft;
use craft\services\Users;

/**
 * Class SproutImport_MockDataService
 *
 * Various methods to help with importing mock seed data into fields and elements
 *
 * @package Craft
 */
class FieldImporter extends Component
{
    /**
     * Generate mock data for all supported fields associated with an Element
     *
     * @param $fields
     *
     * @return array
     */
    public function getFieldsWithMockData($fields)
    {
        $fieldsWithMockData = [];

        if (!empty($fields)) {
            foreach ($fields as $field) {
                $fieldHandle = $field->handle;

                $fieldType = get_class($field);

                $fieldImporterClass = SproutImport::$app->importers->getFieldImporterClassByType($fieldType);

                if ($fieldImporterClass !== null) {
                    /**
                     * @var BaseFieldImporter $fieldImporter
                     */
                    $fieldImporter = new $fieldImporterClass();
                    $fieldImporter->setModel($field);

                    $mockData = $fieldImporter->getMockData();

                    // Only override our field value with mock data if we get data back
                    if ($mockData) {
                        $fieldsWithMockData[$fieldHandle] = $mockData;
                    }
                }
            }
        }

        return $fieldsWithMockData;
    }

    /**
     * @param Element    $element
     * @param array|null $attributes
     * @param            $mockDataSettings
     *
     * @return array|bool
     */
    public function getMockRelations(Element $element, array $attributes = null, $mockDataSettings)
    {
        $results = $element::findAll($attributes);

        if (!$results) {
            SproutImport::info(Craft::t('sprout-import', 'Unable to generate mock {displayName} relations. No relationships found: {elementClass}', [
                'displayName' => $element::displayName(),
                'elementClass' => get_class($element)
            ]));
            return false;
        }

        $total = count($results);

        $fieldName = $mockDataSettings['fieldName'];
        $required = $mockDataSettings['required'];
        $relatedMin = $mockDataSettings['relatedMax'];
        $relatedMax = $mockDataSettings['relatedMax'];

        // If $relatedMin is less than one and the field is required, use the value 1
        if ($relatedMin < 1 && $required === true) {
            $relatedMin = 1;
        }

        // If $relatedMax is greater than the total number of elements, use total elements
        if ($relatedMax > $total || $relatedMax === '') {
            $relatedMax = $total;
        }

        if ($relatedMin > $relatedMax) {
            SproutImport::info(Craft::t('sprout-import', 'Unable to generate Mock Data for relations field: {fieldName}. The minimum amount of relations are less than the total available relations. Make sure your mock settings are valid and that you have content available to relate to all required relations fields.', [
                'fieldName' => $fieldName
            ]));
            return false;
        }

        $randomTotalRelations = random_int($relatedMin, $relatedMax);

        $randomKeys = array_rand($results, $randomTotalRelations);

        $keys = (!is_array($randomKeys)) ? [$randomKeys] : $randomKeys;

        $elementIds = [];

        if (!empty($keys)) {
            foreach ($keys as $key) {
                $elementIds[] = $results[$key]->id;
            }
        }

        return $elementIds;
    }

    /**
     * Determine the Element Group IDs from the source settings
     *
     * @param $sources
     *
     * @return array
     */
    public function getElementGroupIds($sources)
    {
        $ids = [];

        if (!empty($sources)) {
            if ($sources == '*') {
                return $sources;
            }

            foreach ($sources as $source) {
                $ids[] = $this->getElementGroupId($source);
            }
        }

        return $ids;
    }

    /**
     * Extract a specific Element Group ID from the source setting syntax
     *
     * Examples:
     * - group:1
     * - taggroup:1
     *
     * @param $source
     *
     * @return mixed
     */
    public function getElementGroupId($source)
    {
        if ($source === null) {
            return null;
        }

        if ($source == 'singles') {
            return $source;
        }

        $sourceExplode = explode(':', $source);

        return $sourceExplode[1];
    }

    /**
     * Determine what limit to use for a field that has a limit setting.
     *
     * If a limit is set, use that limit. If the limit is infinite, use a reasonable default.
     *
     * @param     $limitFromSettings
     * @param int $defaultLimit
     *
     * @return int
     */
    public function getLimit($limitFromSettings, $defaultLimit = 3)
    {
        if ($limitFromSettings > 0) {
            return $limitFromSettings;
        }

        return $defaultLimit;
    }

    /**
     * Return a random selection of items from an array.
     *
     * Useful for fields such as Multi-select and Checkboxes
     *
     * @param $values
     * @param $number
     *
     * @return array|mixed
     */
    public function getRandomArrayItems($values, $number)
    {
        $randomItems = array_rand($values, $number);

        if (!is_array($randomItems)) {
            return [$randomItems];
        }

        return $randomItems;
    }

    /**
     * Return selected values by keys for use with fields such as Multi-select and Checkboxes
     *
     * @param $keys
     * @param $options
     *
     * @return array
     */
    public function getOptionValuesByKeys($keys, $options)
    {
        $values = [];

        foreach ($keys as $key) {
            $values[] = $options[$key]['value'];
        }

        return $values;
    }

    /**
     * Return a single random value for a set of given options
     *
     * Example $options format:
     * [
     *   0 => [
     *      'label' => 'Label',
     *      'value' => 'Value',
     *      'default' => ''
     *   ]
     * ]
     *
     * @param $options
     *
     * @return mixed
     */
    public function getRandomOptionValue($options)
    {
        $randKey = array_rand($options, 1);

        return $options[$randKey]['value'];
    }

    /**
     * Generate a fake time
     *
     * @param $time
     * @param $increment
     *
     * @return string
     */
    public function getMinutesByIncrement($time, $increment)
    {
        $hour = date('g', $time);
        $minutes = date('i', $time);
        $amPm = date('A', $time);

        $timeMinute = $minutes - ($minutes % $increment);

        if ($timeMinute === 0) {
            $timeMinute = '00';
        }

        return $hour.':'.$timeMinute.' '.$amPm;
    }

    /**
     * Generate columns for the Table Field Importer
     *
     * @param $columns
     *
     * @return array
     */
    public function generateTableColumns($columns)
    {
        $values = [];

        foreach ($columns as $key => $column) {
            $values[$key] = $this->generateTableColumn($column);
        }

        return $values;
    }

    /**
     * Generate a specific column for the Table Field Importer
     *
     * @param $column
     *
     * @return array|int|string
     */
    public function generateTableColumn($column)
    {
        $value = '';
        $fakerService = SproutImport::$app->faker->getGenerator();

        if (!empty($column)) {
            $type = $column['type'];

            switch ($type) {
                case 'singleline':

                    $value = $fakerService->text(50);

                    break;

                case 'multiline':
                    $lines = random_int(2, 3);

                    $value = $fakerService->sentences($lines, true);

                    break;

                case 'number':

                    $value = $fakerService->randomDigit;

                    break;

                case 'checkbox':

                    $value = random_int(0, 1);

                    break;
            }
        }

        return $value;
    }

    /**
     * Generate a fake name or email
     *
     * @param       $nameOrEmail
     * @param       $faker
     * @param bool  $isEmail
     * @param Users $usersService
     *
     * @return mixed
     */
    public function generateUsernameOrEmail($nameOrEmail, $faker, $isEmail = false, $usersService = null)
    {
        if ($usersService == null) {

            $usersService = Craft::$app->getUsers();
        }

        if ($usersService->getUserByUsernameOrEmail($nameOrEmail) != null) {
            if ($isEmail == true) {
                $fakeParam = $faker->email;
            } else {
                $fakeParam = $faker->userName;
            }

            return $this->generateUsernameOrEmail($fakeParam, $faker, $isEmail);
        }

        return $nameOrEmail;
    }
}
