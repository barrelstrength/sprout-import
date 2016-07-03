<?php
namespace Craft;

class SproutImportPlugin extends BasePlugin
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return 'Sprout Import';
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return 'Everything is better in Craft. Import content and settings. Generate fake data.'|t;
	}

	/**
	 * @return string
	 */
	public function getVersion()
	{
		return '0.4.0';
	}

	/**
	 * @return string
	 */
	public function getSchemaVersion()
	{
		return '0.4.0';
	}

	/**
	 * @return string
	 */
	public function getDeveloper()
	{
		return 'Barrel Strength Design';
	}

	/**
	 * @return string
	 */
	public function getDeveloperUrl()
	{
		return 'http://barrelstrengthdesign.com';
	}

	/**
	 * @return string
	 */
	public function getDocumentationUrl()
	{
		return 'http://sprout.barrelstrengthdesign.com/craft-plugins/import/docs';
	}

	/**
	 * @return string
	 */
	public function getReleaseFeedUrl()
	{
		return 'https://sprout.barrelstrengthdesign.com/craft-plugins/import/releases.json';
	}

	/**
	 * @return bool
	 */
	public function hasCpSection()
	{
		return true;
	}

	/**
	 * @return array
	 */
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
	 * @throws \Exception
	 */
	public function init()
	{
		parent::init();

		Craft::import('plugins.sproutimport.contracts.BaseSproutImportElementImporter');
		Craft::import('plugins.sproutimport.contracts.BaseSproutImportFieldImporter');
		Craft::import('plugins.sproutimport.contracts.BaseSproutImportImporter');
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
			new PositionSelectSproutImportFieldImporter(),
			new MultiSelectSproutImportFieldImporter(),
			new TableSproutImportFieldImporter(),
			new EntriesSproutImportFieldImporter(),
			new CategoriesSproutImportFieldImporter(),
			new TagsSproutImportFieldImporter(),
			new AssetsSproutImportFieldImporter()
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
