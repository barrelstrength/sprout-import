<?php
namespace Craft;

class SproutImport_SeedController extends BaseController
{
	/**
	 * Seed Index Template
	 *
	 * @throws HttpException
	 */
	public function actionIndexTemplate()
	{
		$elementSelect = array();

		$elementSelect['Entry']    = 'Entries';
		$elementSelect['Category'] = 'Categories';
		$elementSelect['Tag']      = 'Tags';

		$importers = sproutImport()->getSproutImportImporters();

		$settingElements = "";

		if (!empty($importers))
		{
			foreach ($importers as $importer)
			{
				if ($importer->isElement())
				{
					$settingElements .= $importer->getSettingsHtml() . "\n";
				}
			}
		}

		craft()->templates->includeJsResource('sproutimport/js/sproutimport.js');

		$this->renderTemplate('sproutimport/seed', array(
			'elements' => $elementSelect,
			'settings' => array(
				'elements' => TemplateHelper::getRaw($settingElements)
			)
		));
	}

	/**
	 * Generate Seed Elements
	 *
	 * @throws HttpException
	 */
	public function actionGenerateSeedElements()
	{
		$this->requirePostRequest();

		$elementType = craft()->request->getRequiredPost('elementType');
		$settings    = craft()->request->getRequiredPost('settings');

		if (!empty($elementType))
		{
			$namespace = 'Craft\\' . $elementType . 'SproutImportElementImporter';

			$importerClass = new $namespace;

			$ids = $importerClass->getMockData($settings);
			
			if (!empty($ids))
			{
				foreach ($ids as $id)
				{
					sproutImport()->seed->trackSeed($id, $elementType, 'fake');
				}
			}
		}

		craft()->userSession->setNotice(Craft::t('Elements generated.'));

		$this->redirectToPostedUrl();
	}

	/**
	 * Weed page index
	 * @throws HttpException
	 */

	public function actionWeedIndex()
	{
		$elementImporters = sproutImport()->getSproutImportImporters();

		craft()->templates->includeCssResource('sproutimport/css/sproutimport.css');
		$this->renderTemplate('sproutimport/weed', array(
			'elementImporters' => $elementImporters
		));
	}

	/**
	 * Remove all Seed entries from the database
	 */
	public function actionRunWeed()
	{
		$this->requirePostRequest();

		$submit = craft()->request->getPost('submit');
		$type   = craft()->request->getPost('type');
		$class  = craft()->request->getPost('class');

		if ($submit == "Weed")
		{
			if (craft()->sproutImport_seed->weed($class, $type))
			{
				craft()->userSession->setNotice(Craft::t('The garden is weeded!'));
			}
			else
			{
				craft()->userSession->setError(Craft::t('No luck weeding. Try again.'));
			}
		}
		else if ($submit == "Keep")
		{
			if (craft()->sproutImport_seed->weed($class, $type, true))
			{
				craft()->userSession->setNotice(Craft::t('Data Kept!'));
			}
			else
			{
				craft()->userSession->setError(Craft::t('There is a problem on keeping data. Try again.'));
			}
		}
	}
}
