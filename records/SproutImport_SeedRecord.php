<?php
namespace Craft;

class SproutImport_SeedRecord extends BaseRecord
{
	/**
	 * Return table name
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutimport_seeds';
	}

	/**
	 * These have to be explicitly defined in order for the plugin to install
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'itemId'        => array(AttributeType::Number, 'required' => true),
			'importerClass' => array(AttributeType::String, 'required' => true),
			'type'          => array(AttributeType::String),
			'details'       => array(AttributeType::String),
			'dateSubmitted' => array(AttributeType::DateTime)
		);
	}
}
