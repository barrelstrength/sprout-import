<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170130_040336_sproutimport_addTypeDetailsColumnToSeedsTable extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (($table = $this->dbConnection->schema->getTable('{{sproutimport_seeds}}')))
		{
			if (($column = $table->getColumn('details')) == null)
			{
				$definition = array(
					AttributeType::Mixed,
					'column'   => ColumnType::Text,
					'required' => false
				);

				$this->addColumnAfter('sproutimport_seeds', 'details', $definition, 'importerClass');
			}
			else
			{
				Craft::log('Tried to add a `details` column to the `sproutimport_seeds` table, but there is already
				one there.', LogLevel::Warning);
			}

			if (($column = $table->getColumn('type')) == null)
			{
				$definition = array(
					AttributeType::Mixed,
					'column'   => ColumnType::Text,
					'required' => false
				);

				$this->addColumnAfter('sproutimport_seeds', 'type', $definition, 'importerClass');
			}
			else
			{
				Craft::log('Tried to add a `type` column to the `sproutimport_seeds` table, but there is already
				one there.', LogLevel::Warning);
			}
		}
		else
		{
			Craft::log('Could not find the `sproutimport_seeds` table.', LogLevel::Error);
		}

		return true;
	}
}
