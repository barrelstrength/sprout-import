<?php
namespace Craft;

class SproutMigratePlugin extends BasePlugin
{
	public function getName()
	{
		return 'Sprout Migrate';
	}

	public function getVersion()
	{
		return '0.1.0';
	}

	public function getDeveloper()
	{
		return 'Barrel Strength Design';
	}

	public function getDeveloperUrl()
	{
		return 'http://barrelstrengthdesign.com';
	}

	public function hasCpSection()
	{
		return true;
	}

	public function registerCpRoutes()
	{
		return array(
			'sproutmigrate/start/'                       => array('action' => 'sproutMigrate/start'),
			'sproutmigrate/run/[a-zA-Z]+/[a-zA-Z0-9\-]+' => array('action' => 'sproutMigrate/runTask')
		);
	}
}

/**
 * @return SproutMigrateService
 */
function sproutMigrate()
{
	return craft()->sproutMigrate;
}
