<?php
namespace Craft;

class SproutImport_SeedService extends BaseApplicationComponent
{
	public $seed = false;

	/**
	 * @param null   $itemId
	 * @param null   $importerClass
	 * @param string $type
	 *
	 * @return bool
	 */
	public function trackSeed($itemId = null, $importerClass = null)
	{
		if (!$itemId OR !$importerClass)
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
	 * @param $type
	 *
	 * @return array|\CDbDataReader
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
		$results = $command->queryAll();

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		foreach ($results as $row)
		{
			try
			{
				if (!$isKeep)
				{
					// @todo - improve how we handle this setting...
					// we're just appending 'Model' and adding it to the array here...
					$row['@model'] = $row['importerClass'] . 'Model';

					$importer = sproutImport()->getImporter($row);
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
