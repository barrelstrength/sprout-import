<?php

namespace barrelstrength\sproutimport\services;


use barrelstrength\sproutbase\app\import\base\ElementImporter;

use barrelstrength\sproutbase\app\import\base\SettingsImporter;
use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutimport\models\jobs\SeedJob as SeedJobModel;
use barrelstrength\sproutimport\queue\jobs\Seed as SeedJob;
use barrelstrength\sproutimport\models\Weed;
use barrelstrength\sproutimport\SproutImport;
use craft\base\Component;
use Craft;
use craft\db\Query;
use barrelstrength\sproutimport\models\Seed as SeedModel;
use barrelstrength\sproutimport\records\Seed as SeedRecord;

class Seed extends Component
{
    /**
     * Return all imported content and settings marked as seed data
     *
     * @return array
     */
    public function getAllSeeds()
    {
        $query = new Query();

        $seeds = $query->select('*')
            ->from('{{%sproutimport_seeds}}')
            ->all();

        return $seeds;
    }

    /**
     * @param $seedJobModel SeedJobModel
     *
     * @return bool
     */
    public function generateSeeds(SeedJobModel $seedJobModel)
    {
        if (!$seedJobModel->validate()) {
            return false;
        }

        try {
            Craft::$app->queue->push(new SeedJob([
                'seedJob' => $seedJobModel->getAttributes()
            ]));

            return true;
        } catch (\Exception $e) {
            SproutImport::error($e->getMessage());
        }

        return false;
    }

    /**
     * Mark an item being imported as seed data
     *
     * @param SeedModel $model
     *
     * @return bool
     */
    public function trackSeed(SeedModel $model)
    {
        $itemId = $model->itemId;

        $record = SeedRecord::find()->where(['itemId' => $itemId])->one();

        $result = false;

        // Avoids duplicate tracking
        if ($record == null) {
            $record = new SeedRecord();

            $recordAttributes = $model->getAttributes();

            if (!empty($recordAttributes)) {
                foreach ($recordAttributes as $handle => $value) {
                    if (!empty($value)) {
                        $record->setAttribute($handle, $value);
                    }
                }
            }

            $result = $record->save();
        }

        return $result;
    }

    /**
     * Remove a group of items from the database that are marked as seed data as identified by their class handle
     *
     * @param array $seeds
     * @param bool  $isKeep
     *
     * @return bool
     * @throws \yii\db\Exception
     */
    public function weed(array $seeds = [], $isKeep = false)
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        if (!empty($seeds)) {
            foreach ($seeds as $seed) {
                try {
                    if (!$isKeep) {
                        $row = [];
                        // we're just appending 'Model' and adding it to the array here...
                        $row['@model'] = $seed['importerClass'];

                        /**
                         * @var ElementImporter|SettingsImporter
                         */
                        $importerClass = SproutBase::$app->importers->getImporter($row);

                        $importerClass->deleteById($seed['itemId']);
                    }

                    SproutImport::$app->seed->deleteSeedById($seed['id']);
                } catch (\Exception $e) {
                    SproutImport::error($e->getMessage());

                    return false;
                }
            }

            if ($transaction) {
                $transaction->commit();
            }

            return true;
        }

        return false;
    }

    /**
     * Delete seed data from the database by id
     *
     * @param $id
     *
     * @return int
     * @throws \yii\db\Exception
     */
    public function deleteSeedById($id)
    {
        return Craft::$app->getDb()->createCommand()->delete(
            'sproutimport_seeds',
            'id=:id',
            [':id' => $id]
        )->execute();
    }

    /**
     * Get the number of seed items in the database for element class type
     *
     * @param $handle
     *
     * @return string
     */
    public function getSeedCountByElementType($handle)
    {
        $count = SeedRecord::model()->countByAttributes([
            'importerClass' => $handle
        ]);

        if ($count) {
            return $count;
        } else {
            return '0';
        }
    }

    public function getSeeds()
    {
        $query = new Query();
        $seeds = $query
            ->select('GROUP_CONCAT(id) ids, type, details, COUNT(1) as total, dateCreated')
            ->from('{{%sproutimport_seeds}}')
            ->groupBy(['dateCreated', 'details', 'type'])
            ->orderBy('dateCreated DESC')
            ->all();

        return $seeds;
    }

    /**
     * Returns seeds by dateCreated
     *
     * @param $date
     *
     * @return array
     */
    public function getSeedsByDateCreated($date)
    {
        $query = new Query();

        $seeds = $query
            ->select('*')
            ->from('{{%sproutimport_seeds}}')
            ->where('dateCreated=:dateCreated', [':dateCreated' => $date])
            ->all();

        return $seeds;
    }

    /**
     * @param array $seedJob
     *
     * @return bool
     * @throws \Exception
     */
    public function runSeed(array $seedJob)
    {
        $qty = $seedJob['quantity'];
        $details = $seedJob['details'];

        $weedModelAttributes = [
            'seed' => true,
            'type' => $seedJob['type'],
            'details' => $details,
            'dateCreated' => $seedJob['dateCreated']
        ];

        try {
            $weedModel = new Weed();

            $weedModel->setAttributes($weedModelAttributes, false);

            $elementType = $seedJob['elementType'];
            $settings = $seedJob['settings'];

            /**
             * @var $importerClass ElementImporter
             */
            $importerClass = new $elementType;

            for ($count = 1; $count <= $qty; $count++) {

                $seed = $importerClass->getMockData(1, $settings);

                SproutBase::$app->importers->save($seed, $weedModel);

                $errors = SproutImport::$app->utilities->getErrors();

                if (!empty($errors)) {

                    Craft::error('Unable to save Seed data.', 'sprout-import');
                    Craft::error($errors, 'sprout-import');

                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {

            Craft::error('Unable to save Seed data. Rolling back.', 'sprout-import');
            Craft::error($e->getMessage());

            throw $e;
        }
    }
}
