<?php
namespace Craft;

class SproutImportPlugin extends BasePlugin
{
	public function init()
	{
		parent::init();

		Craft::import('plugins.sproutimport.contracts.*');
		Craft::import('plugins.sproutimport.integrations.sproutimport.*');

		if (craft()->request->isCpRequest() && craft()->request->getSegment(1) == 'sproutimport')
		{
			craft()->templates->includeJsResource("sproutimport/js/sproutimport.js");
		}

		craft()->on('sproutImport.onAfterMigrateElement', function(Event $event) {

			$element = $event->params['element'];
			$seed    = $event->params['seed'];
			$type    = $event->params['@model'];

			$id = $element->id;

			if ($seed)
			{
				sproutImport()->seed->trackSeed($id, $type);
			}

		});
	}

	public function getName()
	{
		return 'Sprout Import';
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
			'sproutimport/start/'                       => array('action' => 'sproutImport/start'),
			'sproutimport/run/[a-zA-Z]+/[a-zA-Z0-9\-]+' => array('action' => 'sproutImport/runTask'),
			'sproutimport/generatedata'                 => array('action' => 'sproutImport/generateData')
		);
	}

	/**
	 * Register built in importers and the method for the integration
	 *
	 * @return array
	 */
	public function registerSproutImportImporters()
	{
		return array(
			new EntrySproutImportImporter(),
			new TagSproutImportImporter(),
			new UserSproutImportImporter(),
			new CategorySproutImportImporter(),
			new EntryTypeSproutImportImporter(),
			new FieldSproutImportImporter(),
			new SectionSproutImportImporter()
		);
	}
}

/**
 * @return SproutImportService
 */
function sproutImport()
{
	return craft()->sproutImport;
}
