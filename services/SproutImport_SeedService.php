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
	public function trackSeed($itemId = null, $importerClass = null, $type = 'import')
	{
		if (!$itemId OR !$importerClass)
		{
			return false;
		}

		$record                = new SproutImport_SeedRecord;
		$record->itemId        = $itemId;
		$record->importerClass = $importerClass;
		$record->type          = $type;

		$record->save();
	}

	/**
	 * @param $type
	 *
	 * @return array|\CDbDataReader
	 */
	public function getAllSeeds($type)
	{
		$seeds = craft()->db->createCommand()
			->select('itemId, importerClass')
			->where("type = '$type'")
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
	public function weed($handle, $type, $isKeep = false)
	{
		$results = craft()->db->createCommand()
			->select('id, itemId, importerClass')
			->where("type = '$type'")
			->andWhere("importerClass = '$handle'")
			->from('sproutimport_seeds')
			->queryAll();

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

	public function getSeedCountByElementType($handle, $type)
	{
		$count = SproutImport_SeedRecord::model()->countByAttributes(array('importerClass' => $handle, 'type' => $type));

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
