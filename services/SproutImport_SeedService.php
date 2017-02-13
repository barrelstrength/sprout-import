<?php
namespace Craft;

class SproutImport_SeedService extends BaseApplicationComponent
{
	/**
	 * Return all imported content and settings marked as seed data
	 *
	 * @return array
	 */
	public function getAllSeeds()
	{
		$seeds = craft()->db->createCommand()
			->select('*')
			->from('sproutimport_seeds')
			->queryAll();

		return $seeds;
	}

	/**
	 * Mark an item being imported as seed data
	 *
	 * @param null $itemId
	 * @param null $importerClass
	 *
	 * @return bool
	 */
	public function trackSeed(SproutImport_SeedModel $model)
	{
		$itemId = $model->itemId;

		$record = SproutImport_SeedRecord::model()->findByAttributes(array('itemId' => $itemId));

		// Avoids duplicate tracking
		if ($record == null)
		{
			$record                = new SproutImport_SeedRecord;

			$recordAttributes = $model->getAttributes();

			if (!empty($recordAttributes))
			{
				foreach ($recordAttributes as $handle => $value)
				{
					$record->setAttribute($handle, $value);
				}
			}

			$record->save();
		}
	}

	/**
	 * Remove a group of items from the database that are marked as seed data as identified by their class handle
	 *
	 * @param $type
	 *
	 * @return bool
	 * @throws \CDbException
	 */
	public function weed($seeds = array(), $isKeep = false)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		if (!empty($seeds))
		{
			foreach ($seeds as $seed)
			{
				try
				{
					if (!$isKeep)
					{
						$row = array();
						// we're just appending 'Model' and adding it to the array here...
						$row['@model'] = $seed['importerClass'] . 'Model';

						$modelName = sproutImport()->getImporterModelName($row);
						$importer  = sproutImport()->getImporterByModelName($modelName, $row);
						$importer->deleteById($seed['itemId']);
					}

					sproutImport()->seed->deleteSeedById($seed['id']);

				}
				catch (\Exception $e)
				{
					SproutImportPlugin::log($e->getMessage());

					return false;
				}
			}

			if ($transaction && $transaction->active)
			{
				$transaction->commit();
			}

			return true;
		}


	}

	/**
	 * Delete seed data from the database by id
	 *
	 * @param $id
	 *
	 * @return int
	 */
	public function deleteSeedById($id)
	{
		return craft()->db->createCommand()->delete(
			'sproutimport_seeds',
			'id=:id',
			array(':id' => $id)
		);
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
		$count = SproutImport_SeedRecord::model()->countByAttributes(array('importerClass' => $handle));

		if ($count)
		{
			return $count;
		}
		else
		{
			return "0";
		}
	}

	public function getSeeds()
	{
		$seeds = craft()->db->createCommand()
			->select('GROUP_CONCAT(id) ids, type, details, COUNT(1) as total, dateSubmitted')
			->from('sproutimport_seeds')
			->group('dateSubmitted, details')
			->order('dateSubmitted DESC')
			->queryAll();

		return $seeds;
	}

	/**
	 * Returns seeds by dateSubmitted
	 *
	 * @param $date
	 *
	 * @return array|\CDbDataReader
	 */
	public function getSeedsByDateSubmitted($date)
	{
		$seeds = craft()->db->createCommand()
			->select('*')
			->from('sproutimport_seeds')
			->where('dateSubmitted=:dateSubmitted', array(':dateSubmitted' => $date))
			->queryAll();

		return $seeds;
	}

	public function getSeedTasks(SproutImport_SeedTasksModel $seedTasksModel)
	{
		$quantity    = $seedTasksModel->quantity;
		$batch       = $seedTasksModel->batch;
		$settings    = $seedTasksModel->settings;
		$elementType = $seedTasksModel->elementType;

		$namespace = 'Craft\\' . $elementType . 'SproutImportElementImporter';
		/**
		 * @var BaseSproutImportElementImporter $importerClass
		 */
		$importerClass = new $namespace;

		$seedTasks = array();

		if ($batch > $quantity)
		{
			$batch = $quantity;
		}

		if ($quantity > $batch || $quantity == $batch)
		{
			$steps = floor($quantity / $batch);

			$mod = $quantity % $batch;

			for ($i = 1; $i <= $steps; $i++)
			{
				$seedTasks[] = $importerClass->getMockData($batch, $settings);
			}

			if ($mod > 0)
			{
				$seedTasks[] = $importerClass->getMockData($mod, $settings);
			}
		}

		return $seedTasks;
	}

	public function seedTasks(SproutImport_SeedTasksModel $seedTasksModel)
	{
		$quantity    = $seedTasksModel->quantity;
		$batch       = $seedTasksModel->batch;
		$settings    = $seedTasksModel->settings;
		$elementType = $seedTasksModel->elementType;

		$namespace = 'Craft\\' . $elementType . 'SproutImportElementImporter';
		/**
		 * @var BaseSproutImportElementImporter $importerClass
		 */
		$importerClass = new $namespace;

		if ($quantity)
		{
			for ($i = 1; $i <= $quantity; $i++)
			{
				$seedTask = $importerClass->getMockData($batch, $settings);

				sproutImport()->tasks->createSeedTasks($seedTask, $seedTasksModel);
			}
		}
	}
}
