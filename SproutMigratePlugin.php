<?php
namespace Craft;

class SproutMigratePlugin extends BasePlugin
{
	public function init()
	{
		craft()->on('sproutMigrate.beforeMigrateElement', array($this, 'handleElementMigration'));
	}

	public function getName()
	{
		return 'Sprout Migrate';
	}

	public function getVersion()
	{
		return '0.4.0';
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

	protected function handleElementMigration(Event $event)
	{
		/**
		 * @var $element EntryModel
		 */
		$element = $event->params['element'];

		if ($element->getElementType() == ElementType::Entry)
		{
			// Do something
		}
	}
}

/**
 * @return SproutMigrateService
 */
function sproutMigrate()
{
	return craft()->sproutMigrate;
}
