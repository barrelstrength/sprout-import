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
			if (($column = $table->getColumn('dateSubmitted')) == null)
			{
				$definition = array(
					AttributeType::Mixed,
					'column'   => ColumnType::DateTime,
					'required' => false
				);

				$this->addColumnAfter('sproutimport_seeds', 'dateSubmitted', $definition, 'importerClass');
			}
			else
			{
				Craft::log('The `dateSubmitted` column already exists in the `sproutimport_seeds` table.', LogLevel::Warning);
			}

			if (($column = $table->getColumn('details')) == null)
			{
				$definition = array(
					AttributeType::String,
					'column'   => ColumnType::Varchar,
					'required' => false,
					'default' => null
				);

				$this->addColumnAfter('sproutimport_seeds', 'details', $definition, 'importerClass');
			}
			else
			{
				Craft::log('The `details` column already exists in the `sproutimport_seeds` table..', LogLevel::Warning);
			}

			if (($column = $table->getColumn('type')) == null)
			{
				$definition = array(
					AttributeType::String,
					'column'   => ColumnType::Varchar,
					'required' => false,
					'default' => null
				);

				$this->addColumnAfter('sproutimport_seeds', 'type', $definition, 'importerClass');
			}
			else
			{
				Craft::log('The `type` column already exists in the `sproutimport_seeds` table.', LogLevel::Warning);
			}
		}
		else
		{
			Craft::log('Could not find the `sproutimport_seeds` table.', LogLevel::Error);
		}

		return true;
	}
}
