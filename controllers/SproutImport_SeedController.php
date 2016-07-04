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

		$allSeedImporters        = sproutImport()->getSproutImportSeedImporters();
		$registeredSeedImporters = $allSeedImporters;

		// Create an array of all registered Sprout Import Importers
		$defaultSeedImporters = $registeredSeedImporters['SproutImport'];
		unset($registeredSeedImporters['SproutImport']);

		// Create an array of all registered third-party Importers
		$customSeedImporters = $registeredSeedImporters;

		if (!empty($defaultSeedImporters))
		{
			$elementSelect[] = array(
				'optgroup' => Craft::t('Standard Elements')
			);

			foreach ($defaultSeedImporters as $importer)
			{
				if ($importer->isElement())
				{
					$title = $importer->getElement()->getName();
				}
				else
				{
					$title = $importer->getName();
				}

				$classId = 'SproutImport-' . $importer->getName();

				$elementSelect[$classId] = array(
					'label' => $title,
					'value' => $importer->getName()
				);
			}
		}

		if (!empty($customSeedImporters))
		{
			$elementSelect[] = array(
				'optgroup' => Craft::t('Custom Elements')
			);

			foreach ($customSeedImporters as $importer)
			{
				foreach ($importer as $plugin => $importerClass)
				{
					if ($importerClass->isElement())
					{
						$title = $importerClass->getElement()->getName();
					}
					else
					{
						$title = $importerClass->getName();
					}

					$classId = $plugin . '-' . $importerClass->getName();

					$elementSelect[$classId] = array(
						'label' => $title,
						'value' => $importerClass->getName()
					);
				}
			}
		}

		$this->renderTemplate('sproutimport/seed', array(
			'elements'  => $elementSelect,
			'importers' => $allSeedImporters
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
		$quantity    = craft()->request->getRequiredPost('quantity');
		$settings    = craft()->request->getRequiredPost('settings');

		if (!empty($elementType))
		{
			$namespace = 'Craft\\' . $elementType . 'SproutImportElementImporter';

			$importerClass = new $namespace;

			$ids = $importerClass->getMockData($quantity, $settings);

			if (!empty($ids))
			{
				foreach ($ids as $id)
				{
					sproutImport()->seed->trackSeed($id, $elementType);
				}
				craft()->userSession->setNotice(Craft::t('Elements generated.'));
			}
			else
			{
				$errors = sproutImport()->getErrors();

				if (!empty($errors))
				{
					$msg = implode("\n", $errors);
					sproutImport()->error($msg);

					craft()->userSession->setError(Craft::t('Unable to generate data. Check logs.'));
				}
			}
		}

		$this->redirectToPostedUrl();
	}

	/**
	 * Weed page index
	 *
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
		else
		{
			if ($submit == "Keep" || $submit == "Keep All")
			{
				if (craft()->sproutImport_seed->weed($class, true))
				{
					craft()->userSession->setNotice(Craft::t('Data Kept!'));
				}
				else
				{
					craft()->userSession->setError(Craft::t('Unable to keep data. Try again.'));
				}
			}
		}
	}
}
