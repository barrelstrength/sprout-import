<?php
namespace Craft;

class SproutImport_SeedTasksModel extends BaseModel
{
	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'elementType'   => array(AttributeType::String),
			'batch'         => array(AttributeType::Number),
			'quantity'      => array(AttributeType::Number),
			'settings'      => array(AttributeType::Mixed)
		);
	}
}