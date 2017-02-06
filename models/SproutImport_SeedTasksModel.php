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
			'elementType'   => array(AttributeType::String, 'required' => true, 'default' => 'Entry'),
			'batch'         => array(AttributeType::Number, 'required' => true, 'default' => 10),
			'quantity'      => array(AttributeType::Number, 'required' => true, 'default' => 11),
			'settings'      => array(AttributeType::Mixed,  'required' => true)
		);
	}
}