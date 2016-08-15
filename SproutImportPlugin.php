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
		return Craft::t('Import content and settings. Generate fake data.');
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
	 * Initialize Sprout Import
	 */
	public function init()
	{
		parent::init();

		$this->includeContracts();

		$this->includeElements();

		$this->includeSettings();

		$this->includeFields();

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
			// Element Importers
			new AssetFileSproutImportElementImporter(),
			new CategorySproutImportElementImporter(),
			new EntrySproutImportElementImporter(),
			new TagSproutImportElementImporter(),
			new UserSproutImportElementImporter(),

			// Settings Importers
			new EntryTypeSproutImportSettingsImporter(),
			new FieldSproutImportSettingsImporter(),
			new SectionSproutImportSettingsImporter()
		);

		// Check if craft commerce plugin is installed and enabled
		$commercePlugin = craft()->plugins->getPlugin('commerce', false);

		// Commerce events goes here
		if (isset($commercePlugin->isEnabled) && $commercePlugin->isEnabled)
		{
			$importers[] = new Commerce_OrderSproutImportElementImporter();
			$importers[] = new Commerce_ProductSproutImportElementImporter();
			$importers[] = new Commerce_ProductTypeSproutImportSettingsImporter();
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
		$fields = array(
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
			new AssetsSproutImportFieldImporter(),
			new UsersSproutImportFieldImporter(),
			new MatrixSproutImportFieldImporter()
		);


		return $fields;
	}

	private function includeContracts()
	{
		$contracts   = array();
		$contracts[] = "BaseSproutImportImporter";
		$contracts[] = "BaseSproutImportElementImporter";
		$contracts[] = "BaseSproutImportSettingsImporter";
		$contracts[] = "BaseSproutImportFieldImporter";

		foreach ($contracts as $contract)
		{
			Craft::import("plugins.sproutimport.contracts.$contract");
		}
	}

	private function includeElements()
	{
		$elements   = array();
		$elements[] = "AssetFileSproutImportElementImporter";
		$elements[] = "CategorySproutImportElementImporter";
		$elements[] = "Commerce_OrderSproutImportElementImporter";
		$elements[] = "Commerce_ProductSproutImportElementImporter";
		$elements[] = "EntrySproutImportElementImporter";
		$elements[] = "TagSproutImportElementImporter";
		$elements[] = "UserSproutImportElementImporter";

		foreach ($elements as $element)
		{
			Craft::import("plugins.sproutimport.integrations.sproutimport.elements.$element");
		}
	}

	private function includeSettings()
	{
		$settings = array();
		$settings[] = "Commerce_ProductTypeSproutImportSettingsImporter";
		$settings[] = "SectionSproutImportSettingsImporter";
		$settings[] = "FieldSproutImportSettingsImporter";
		$settings[] = "EntryTypeSproutImportSettingsImporter";

		foreach ($settings as $setting)
		{
			Craft::import("plugins.sproutimport.integrations.sproutimport.settings.$setting");
		}
	}

	private function includeFields()
	{
		$fields = array();
		$fields[] = "AssetsSproutImportFieldImporter";
		$fields[] = "CategoriesSproutImportFieldImporter";
		$fields[] = "CheckboxesSproutImportFieldImporter";
		$fields[] = "ColorSproutImportFieldImporter";
		$fields[] = "DateSproutImportFieldImporter";
		$fields[] = "DropdownSproutImportFieldImporter";
		$fields[] = "EntriesSproutImportFieldImporter";
		$fields[] = "LightswitchSproutImportFieldImporter";
		$fields[] = "MatrixSproutImportFieldImporter";
		$fields[] = "MultiSelectSproutImportFieldImporter";
		$fields[] = "NumberSproutImportFieldImporter";
		$fields[] = "PlainTextSproutImportFieldImporter";
		$fields[] = "PositionSelectSproutImportFieldImporter";
		$fields[] = "RadioButtonsSproutImportFieldImporter";
		$fields[] = "RichTextSproutImportFieldImporter";
		$fields[] = "TableSproutImportFieldImporter";
		$fields[] = "TagsSproutImportFieldImporter";
		$fields[] = "UsersSproutImportFieldImporter";
		$fields[] = "Commerce_ProductsSproutImportFieldImporter";

		foreach ($fields as $field)
		{
			Craft::import("plugins.sproutimport.integrations.sproutimport.fields.$field");
		}
	}
}

/**
 * @return SproutImportService
 */
function sproutImport()
{
	return craft()->sproutImport;
}
