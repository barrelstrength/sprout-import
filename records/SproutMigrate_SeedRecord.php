<?php
namespace Craft;

class SproutMigrate_SeedRecord extends BaseRecord
{
	/**
	 * Return table name
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutmigrate_seeds';
	}
	
	/**
	 * These have to be explicitly defined in order for the plugin to install
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'itemId' => array(AttributeType::Number, 'required' => true),
			'importerClass' => array(AttributeType::String, 'required' => true),
		);
	}
}
