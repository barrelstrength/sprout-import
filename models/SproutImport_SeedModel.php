<?php
namespace Craft;

class SproutImport_SeedModel extends BaseModel
{
	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'itemId'        => array(AttributeType::Number),
			'importerClass' => array(AttributeType::String),
			'type'          => array(AttributeType::String),
			'details'       => array(AttributeType::String),
			'items'         => array(AttributeType::Number),
			'dateSubmitted' => array(AttributeType::DateTime)
		);
	}
}