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
			'itemId'        => array(AttributeType::Number, 'required' => true),
			'importerClass' => array(AttributeType::String, 'required' => true),
			'type'          => array(AttributeType::String),
			'details'       => array(AttributeType::String),
			'items'         => array(AttributeType::Number),
			'dateSubmitted' => array(AttributeType::DateTime)
		);
	}
}