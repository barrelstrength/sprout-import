<?php
namespace Craft;

class SproutImport_WeedModel extends BaseModel
{
	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'seed'    => array(AttributeType::Bool),
			'type'    => array(AttributeType::String),
			'details' => array(AttributeType::String),
			'dateSubmitted' => array(AttributeType::DateTime)
		);
	}
}