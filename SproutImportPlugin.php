<?php
namespace Craft;

class SproutImportPlugin extends BasePlugin
{
	/**
	 * @return string
	 */
	public function getName()
	{
		$pluginName         = Craft::t('Sprout Import');
		$pluginNameOverride = $this->getSettings()->pluginNameOverride;

		return ($pluginNameOverride) ? $pluginNameOverride : $pluginName;
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
		return '0.4.4';
	}

	/**
	 * @return string
	 */
	public function getSchemaVersion()
	{
		return '0.4.1';
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
			'sproutimport/start/'                       => array(
				'action' => 'sproutImport/start'
			),
			'sproutimport/run/[a-zA-Z]+/[a-zA-Z0-9\-]+' => array(
				'action' => 'sproutImport/runTask'
			),
			'sproutimport/seed'                         => array(
				'action' => 'sproutImport/seed/seedIndexTemplate'
			),
			'sproutimport/weed'                         => array(
				'action' => 'sproutImport/weed/weedIndexTemplate'
			),
			'sproutimport/settings/(general)'           => array(
				'action' => 'sproutImport/settings/settingsIndexTemplate'
			),
		);
	}

	/**
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'pluginNameOverride' => AttributeType::String
		);
	}

	/**
	 * Initialize Sprout Import
	 */
	public function init()
	{
		parent::init();

		$this->importContracts();
		$this->importSproutImportElementImporters();
		$this->importSproutImportSettingsImporters();
		$this->importSproutImportFieldImporters();

		if (craft()->request->isCpRequest() && craft()->request->getSegment(1) == 'sproutimport')
		{
			craft()->templates->includeJsResource("sproutimport/js/sproutimport.js");
		}

		craft()->on('sproutImport.onAfterImportElement', function (Event $event)
		{
			sproutImport()->seed->trackImport($event);
		});

		craft()->on('sproutImport.onAfterImportSetting', function (Event $event)
		{
			sproutImport()->seed->trackImport($event);
		});

		if (craft()->request->isCpRequest() && craft()->request->getSegment(1) == 'sproutimport')
		{
			craft()->templates->includeJsResource('sproutimport/js/brand.js');
			craft()->templates->includeJs("
				sproutFormsBrand = new Craft.SproutBrand();
				sproutFormsBrand.displayFooter({
					pluginName: 'Sprout Import',
					pluginUrl: 'http://sprout.barrelstrengthdesign.com/craft-plugins/import',
					pluginVersion: '" . $this->getVersion() . "',
					pluginDescription: '" . $this->getDescription() . "',
					developerName: '(Barrel Strength)',
					developerUrl: '" . $this->getDeveloperUrl() . "'
				});
			");
		}
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
			new AssetSproutImportElementImporter(),
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
			$craftCommerceImporters = array(
				new Commerce_OrderSproutImportElementImporter(),
				new Commerce_ProductSproutImportElementImporter(),
				new Commerce_ProductTypeSproutImportSettingsImporter()
			);

			$importers = array_merge($importers, $craftCommerceImporters);
		}

		return $importers;
	}

	/**
	 * Register importer fields
	 *
	 * @return array
	 */
	public function registerSproutImportFieldImporters()
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

	private function importContracts()
	{
		$contracts = array(
			"BaseSproutImportImporter",
			"BaseSproutImportElementImporter",
			"BaseSproutImportSettingsImporter",
			"BaseSproutImportFieldImporter"
		);

		foreach ($contracts as $contract)
		{
			Craft::import("plugins.sproutimport.contracts.$contract");
		}
	}

	private function importSproutImportElementImporters()
	{
		$elements = array(
			"AssetSproutImportElementImporter",
			"CategorySproutImportElementImporter",
			"Commerce_OrderSproutImportElementImporter",
			"Commerce_ProductSproutImportElementImporter",
			"EntrySproutImportElementImporter",
			"TagSproutImportElementImporter",
			"UserSproutImportElementImporter"
		);

		foreach ($elements as $element)
		{
			Craft::import("plugins.sproutimport.integrations.sproutimport.elements.$element");
		}
	}

	private function importSproutImportSettingsImporters()
	{
		$settings = array(
			"Commerce_ProductTypeSproutImportSettingsImporter",
			"SectionSproutImportSettingsImporter",
			"FieldSproutImportSettingsImporter",
			"EntryTypeSproutImportSettingsImporter"
		);

		foreach ($settings as $setting)
		{
			Craft::import("plugins.sproutimport.integrations.sproutimport.settings.$setting");
		}
	}

	private function importSproutImportFieldImporters()
	{
		$fields = array(
			"AssetsSproutImportFieldImporter",
			"CategoriesSproutImportFieldImporter",
			"CheckboxesSproutImportFieldImporter",
			"ColorSproutImportFieldImporter",
			"DateSproutImportFieldImporter",
			"DropdownSproutImportFieldImporter",
			"EntriesSproutImportFieldImporter",
			"LightswitchSproutImportFieldImporter",
			"MatrixSproutImportFieldImporter",
			"MultiSelectSproutImportFieldImporter",
			"NumberSproutImportFieldImporter",
			"PlainTextSproutImportFieldImporter",
			"PositionSelectSproutImportFieldImporter",
			"RadioButtonsSproutImportFieldImporter",
			"RichTextSproutImportFieldImporter",
			"TableSproutImportFieldImporter",
			"TagsSproutImportFieldImporter",
			"UsersSproutImportFieldImporter",
			"Commerce_ProductsSproutImportFieldImporter"
		);

		foreach ($fields as $field)
		{
			Craft::import("plugins.sproutimport.integrations.sproutimport.fields.$field");
		}
	}

	/**
	 * Override SproutImportPlugin::log() method to allow the logging of
	 * multiple messages and arrays
	 *
	 * Examples:
	 *
	 * Standard log:
	 * SproutImportPlugin::log($msg);
	 *
	 * Enhanced log:
	 * $messages['thing1'] = Craft::t('Something happened');
	 * $messages['thing2'] = $entry->getErrors();
	 * SproutImportPlugin::log($messages);
	 *
	 * @param string $messages
	 * @param string $level
	 * @param bool   $force
	 *
	 * @return null - writes log to logfile
	 */
	public static function log($messages, $level = LogLevel::Info, $force = false)
	{
		$msg = "";

		if (is_array($messages))
		{
			foreach ($messages as $message)
			{
				$msg .= PHP_EOL . print_r($message, true);
			}
		}
		else
		{
			$msg = $messages;
		}

		parent::log($msg, $level, $force);
	}
}

/**
 * @return SproutImportService
 */
function sproutImport()
{
	return craft()->sproutImport;
}
