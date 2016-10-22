<?php
namespace Craft;

class SproutImport_SettingsModel extends BaseModel
{
	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'pluginNameOverride' => AttributeType::String
		);
	}
}