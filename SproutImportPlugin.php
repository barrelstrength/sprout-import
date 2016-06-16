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

		craft()->on('sproutImport.onAfterImportElement', function (Event $event)
		{
			sproutImport()->trackImport($event);
		});

		craft()->on('sproutImport.onAfterImportSetting', function (Event $event)
		{
			sproutImport()->trackImport($event);
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
		$importers = array(
			new EntrySproutImportElementImporter(),
			new TagSproutImportElementImporter(),
			new AssetSproutImportElementImporter(),
			new UserSproutImportElementImporter(),
			new CategorySproutImportElementImporter(),
			new EntryTypeSproutImportImporter(),
			new FieldSproutImportImporter(),
			new SectionSproutImportImporter()
		);

		// Check if craft commerce plugin is installed and enabled
		$commercePlugin = craft()->plugins->getPlugin('commerce', false);

		// Commerce events goes here
		if (isset($commercePlugin->isEnabled) && $commercePlugin->isEnabled)
		{
			$importers[] = new Commerce_ProductSproutImportElementImporter();
			$importers[] = new Commerce_ProductTypeSproutImportImporter();
		}

		return $importers;
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
			new PlainTextSproutImportFieldImporter(),
			new NumberSproutImportFieldImporter(),
			new CheckboxesSproutImportFieldImporter(),
			new RadioButtonsSproutImportFieldImporter(),
			new ColorSproutImportFieldImporter(),
			new DateSproutImportFieldImporter(),
			new LightswitchSproutImportFieldImporter(),
			new DropdownSproutImportFieldImporter(),
			new PositionSelectSproutImportFieldImporter()
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
