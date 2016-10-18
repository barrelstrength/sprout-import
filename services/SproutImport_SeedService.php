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
			->select('itemId, importerClass')
			->from('sproutimport_seeds')
			->queryAll();

		return $seeds;
	}

	/**
	 * Mark an item being imported as seed data
	 *
	 * @param null   $itemId
	 * @param null   $importerClass
	 *
	 * @return bool
	 */
	public function trackSeed($itemId = null, $importerClass = null)
	{
		if (!$itemId || !$importerClass)
		{
			return false;
		}

		$record = SproutImport_SeedRecord::model()->findByAttributes(array('itemId' => $itemId));

		// Avoids duplicate tracking
		if ($record == null)
		{
			$record                = new SproutImport_SeedRecord;
			$record->itemId        = $itemId;
			$record->importerClass = $importerClass;

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
	public function weed($handle = '', $isKeep = false)
	{
		$command = craft()->db->createCommand();
		$command = $command->select('id, itemId, importerClass');

		if ($handle != "*")
		{
			$command = $command->andWhere("importerClass = '$handle'");
		}

		$command = $command->from('sproutimport_seeds');
		$itemsToWeed = $command->queryAll();

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		foreach ($itemsToWeed as $row)
		{
			try
			{
				if (!$isKeep)
				{
					// @todo - improve how we handle this setting...
					// we're just appending 'Model' and adding it to the array here...
					$row['@model'] = $row['importerClass'] . 'Model';

					$modelName = sproutImport()->getImporterModelName($row);
					$importer  = sproutImport()->getImporterByModelName($modelName, $row);
					$importer->deleteById($row['itemId']);
				}

				$this->deleteSeedById($row['id']);
			}
			catch (\Exception $e)
			{
				SproutImportPlugin::log($e->getMessage());
			}
		}

		if ($transaction && $transaction->active)
		{
			$transaction->commit();
		}

		return true;
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
}
