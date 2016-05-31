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

		//$elementSelect['Entry']    = Craft::t('Entries');
		//$elementSelect['Category'] = Craft::t('Categories');
		//$elementSelect['Tag']      = Craft::t('Tags');

		$settingElements = "";

		$builtInPlugin = 'SproutImport';

		$sproutImportSeeds = craft()->plugins->call('registerSproutImportSeeds');

		$seedClasses = $sproutImportSeeds['SproutImport'];

		if (!empty($seedClasses))
		{

			$elementSelect[] = array(
				'optgroup' => Craft::t('Standard Elements')
			);

			foreach ($seedClasses as $seedClass)
			{
				if ($seedClass->isElement())
				{
					$title = $seedClass->getElement()->getName();
				}
				else
				{
					$title = $seedClass->getName();
				}

				$classId = $builtInPlugin . '-' . $seedClass->getName();

				$elementSelect[$classId] = array(
														'label'    => $title,
			                      'value'    => $seedClass->getName()
													);

				$settingElements .= $seedClass->getSettingsHtml() . "\n";
			}
		}

		$pluginClasses = craft()->plugins->call('registerSproutImportSeeds');
		unset($pluginClasses['SproutImport']);

		if (!empty($pluginClasses))
		{

			$elementSelect[] = array(
				'optgroup' => Craft::t('Custom Elements')
			);

			foreach ($pluginClasses as $customClasses)
			{
				foreach ($customClasses as $plugin => $customClass)
				{
					if ($customClass->isElement())
					{
						$title = $customClass->getElement()->getName();
					}
					else
					{
						$title = $customClass->getName();
					}

					$classId = $plugin . '-' . $customClass->getName();

					$elementSelect[$classId] = array(
						'label'    => $title,
						'value'    => $customClass->getName()
					);

					$settingElements .= $customClass->getSettingsHtml() . "\n";
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
					sproutImport()->seed->trackSeed($id, $elementType);
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
		$class  = craft()->request->getPost('class');

		if ($submit == "Weed" || $submit == "Weed All")
		{
			if (craft()->sproutImport_seed->weed($class))
			{
				craft()->userSession->setNotice(Craft::t('The garden is weeded!'));
			}
			else
			{
				craft()->userSession->setError(Craft::t('No luck weeding. Try again.'));
			}
		}
		else if ($submit == "Keep" || $submit == "Keep All")
		{
			if (craft()->sproutImport_seed->weed($class, true))
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
