<?php
namespace Craft;

class SproutImport_SettingsModel extends BaseModel
{
	protected function defineAttributes()
	{
		return array(
			'pluginNameOverride' => AttributeType::String
		);
	}
}