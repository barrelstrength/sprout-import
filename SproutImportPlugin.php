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

	public function registerSproutImportElements()
	{
		return array(
			'user'     => array(
				'model'   => 'Craft\\UserModel',
				'method'  => 'saveUser',
				'service' => 'users',
			),
			'entry'    => array(
				'model'   => 'Craft\\EntryModel',
				'method'  => 'saveEntry',
				'service' => 'entries',
			),
			'category' => array(
				'model'   => 'Craft\\CategoryModel',
				'method'  => 'saveCategory',
				'service' => 'categories',
			),
			'tag'      => array(
				'model'   => 'Craft\\TagModel',
				'method'  => 'saveTag',
				'service' => 'tags',
			)
		);
	}

	public function registerSproutImportImporters()
	{
		return array(
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
