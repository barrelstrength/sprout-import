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

					$importer = sproutImport()->getImporterByRow($row);
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

	public function getRandomArrays($values, $number)
	{
		$rands = array_rand($values, $number);

		if (!is_array($rands))
		{
			return array($rands);
		}

		return $rands;
	}

	public function getOptionValuesByKeys($keys, $options)
	{
		$values = array();

		foreach ($keys as $key)
		{
			$values[] = $options[$key]['value'];
		}

		return $values;
	}

	public function getRandomOptionValue($options, $key = 'value')
	{
		$randKey = array_rand($options, 1);

		$value = $options[$randKey];

		if ($key == false)
		{
			return $value;
		}

		return $value[$key];
	}

	public function getMinutesByIncrement($time, $increment)
	{
		$hour    = date('g', $time);
		$minutes = date('i', $time);
		$amPm    = date('A', $time);

		$timeMinute = $minutes - ($minutes % $increment);

		if ($timeMinute === 0)
		{
			$timeMinute = "00";
		}

		return $hour . ":" . $timeMinute . " " . $amPm;
	}

	public function getMockFieldsByElementName($elementName)
	{
		$fieldClasses = sproutImport()->getSproutImportFields();

		$fieldValues = array();

		if (!empty($fieldClasses))
		{
			// Get only declared field classes
			foreach ($fieldClasses as $fieldClass)
			{
				$fields = sproutImport()->elements->getFieldsByType($elementName, $fieldClass);

				if (!empty($fields))
				{
					// Loop through all attach fields on this element
					foreach ($fields as $field)
					{
						$fieldClass->setField($field);
						$fieldHandle                             = $field->handle;
						$fieldValues[$fieldHandle] = $fieldClass->getMockData();
					}
				}
			}
		}

		return $fieldValues;
	}

	public function generateColumns($columns)
	{
		$values = array();

		foreach ($columns as $key => $column)
		{
			$values[$key] = $this->generateColumn($key, $column);
		}

		return $values;
	}

	public function generateColumn($key, $column)
	{
		$value = '';
		$fakerService = sproutImport()->faker->getGenerator();

		if (!empty($column))
		{
			$type = $column['type'];

			switch ($type)
			{
				case "singleline":

					$value = $fakerService->text(50);

					break;

				case "multiline":
					$lines     = rand(2, 4);

					$value = $fakerService->sentences($lines, true);

					break;

				case "number":

					$value = $fakerService->randomDigit;

					break;

				case "checkbox":

					$bool = rand(0,1);

					if ($bool === 0)
					{
						$value = '';
					}

					$value = $bool;

					break;
			}
		}

		return $value;
	}

	public function getFindElementSettings(array $settings = array())
	{
		$ids = array();

		$sources = $settings['sources'];

		if (!empty($sources))
		{
			if ($sources == "*")
			{
				return $sources;
			}

			foreach ($sources as $source)
			{
				$ids[] = $this->getElementGroup($source);
			}
		}

		return $ids;
	}

	public function getElementGroup($source)
	{
		$sourceExplode = explode(":", $source);
		return $sourceExplode[1];
	}

	public function getMockFieldElements($elementName, array $find = array(), $limit)
	{
		$criteria = craft()->elements->getCriteria($elementName);

		$results = $criteria->find($find);

		$total   = $criteria->total();

		// swap total elements if limit setting is greater than total find elements
		if ($limit > $total || $limit === '')
		{
			$limit = $total;
		}

		$randomLimit = rand(1, $limit);

		$keys = array();

		$randKeys = array_rand($results, $randomLimit);

		$keys = (!is_array($randKeys)) ? array($randKeys) : $randKeys;

		$elementIds = array();

		if (!empty($keys))
		{
			foreach ($keys as $key)
			{
				$elementIds[] = $results[$key]->id;
			}
		}

		return $elementIds;
	}
}
