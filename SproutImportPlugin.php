<?php
namespace Craft;

class SproutImportPlugin extends BasePlugin
{
	public function init()
	{
		parent::init();

		Craft::import('plugins.sproutimport.contracts.*');
		Craft::import('plugins.sproutimport.integrations.sproutimport.*');
		Craft::import('plugins.sproutimport.integrations.sproutimport.fields.*');

		if (craft()->request->isCpRequest() && craft()->request->getSegment(1) == 'sproutimport')
		{
			craft()->templates->includeJsResource("sproutimport/js/sproutimport.js");
		}

		craft()->on('sproutImport.onAfterMigrateElement', function (Event $event)
		{
			$element = $event->params['element'];
			$seed    = $event->params['seed'];
			$type    = $event->params['@model'];
			$source  = $event->params['source'];

			$id = $element->id;

			if ($seed && $source == "import")
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
			'sproutimport/seed'                         => array('action' => 'sproutImport/seed/indexTemplate'),
			'sproutimport/weed'                         => array('action' => 'sproutImport/seed/weedIndex')
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
			new EntrySproutImportElementImporter(),
			new TagSproutImportElementImporter(),
			new UserSproutImportElementImporter(),
			new CategorySproutImportElementImporter(),
			new EntryTypeSproutImportImporter(),
			new FieldSproutImportImporter(),
			new SectionSproutImportImporter(),
			new Commerce_ProductSproutImportElementImporter()
		);
	}

	/**
	 * Register importer fields
	 *
	 * @return array
	 */
	public function registerSproutImportFields()
	{
		return array(
			new RichTextSproutImportFieldImporter(),
			new PlainTextSproutImportFieldImporter()
		);
	}

	/**
	 * Register classes that can be added on the seeding feature,
	 *
	 * @return array
	 */
	public function registerSproutImportSeeds()
	{
		return array(
			new EntrySproutImportElementImporter(),
			new CategorySproutImportElementImporter(),
			new TagSproutImportElementImporter()
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
