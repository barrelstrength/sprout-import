<?php
namespace Craft;

class SproutImport_SeedController extends BaseController
{
	/**
	 * Seed Index Template
	 *
	 * @throws HttpException
	 */
	public function actionSeedIndexTemplate()
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
				$title = $importer->getName();

				$classId = 'SproutImport-' . $importer->getModelName();

				$elementSelect[$classId] = array(
					'label' => $title,
					'value' => $importer->getModelName()
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
					$title = $importerClass->getName();

					$classId = $plugin . '-' . $importerClass->getModelName();

					$elementSelect[$classId] = array(
						'label' => $title,
						'value' => $importerClass->getModelName()
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

			/**
			 * @var BaseSproutImportElementImporter $importerClass
			 */
			$importerClass = new $namespace;

			$ids = $importerClass->getMockData($quantity, $settings);

			if (!empty($ids))
			{
				foreach ($ids as $id)
				{
					$attributes = array(
						'itemId'        => $id,
						'importerClass' => $elementType,
						'type'          => 'Seed',
						'details'       => $importerClass->getName()
					);

					$seedModel = SproutImport_SeedModel::populateModel($attributes);

					sproutImport()->seed->trackSeed($seedModel);
				}

				craft()->userSession->setNotice(Craft::t('Elements generated.'));
			}
			else
			{
				$errors = sproutImport()->getErrors();

				if (!empty($errors))
				{
					craft()->userSession->setError(Craft::t('Unable to generate data. Check logs.'));

					$message = implode("\n", $errors);

					SproutImportPlugin::log($message, LogLevel::Error);
				}
			}
		}

		$this->redirectToPostedUrl();
	}
}
