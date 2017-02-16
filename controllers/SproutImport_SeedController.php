<?php
namespace Craft;

class SproutImport_SeedController extends BaseController
{
	/**
	 * Seed Index Template
	 *
	 * @throws HttpException
	 */
	public function actionSeedIndexTemplate(array $variables = array())
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

		$seedTaskModel = new SproutImport_SeedTaskModel();

		if (isset($variables['seeds']))
		{
			$seedTaskModel = $variables['seeds'];
		}

		$this->renderTemplate('sproutimport/seed', array(
			'elements'  => $elementSelect,
			'importers' => $allSeedImporters,
			'seeds'     => $seedTaskModel
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

		$plugin         = craft()->plugins->getPlugin('sproutimport');
		$pluginSettings = $plugin->getSettings();

		$batch = (isset($pluginSettings->batch) && $pluginSettings->batch > 0) ? $pluginSettings->batch : 10;

		if (!empty($elementType))
		{
			$weedMessage = Craft::t('{elementType} Element');

			if ($quantity > 1)
			{
				$weedMessage = Craft::t('{elementType} Elements');
			}

			$details = Craft::t($weedMessage, array(
				'elementType' => $elementType
			));

			$seedTaskAttributes = array(
				'elementType'   => $elementType,
				'batch'         => $batch,
				'quantity'      => $quantity,
				'settings'      => $settings,
				'type'          => 'Seed',
				'details'       => $details,
				'dateSubmitted' => DateTimeHelper::currentTimeForDb()
			);

			$seedTaskModel = SproutImport_SeedTaskModel::populateModel($seedTaskAttributes);

			$sets = array();

			if (!empty($settings))
			{
				foreach ($settings as $type => $setting)
				{
					if (!empty($setting))
					{
						$sets[] = $type;
					}
				}

				if (empty($sets))
				{
					$seedTaskModel->addError('settings', Craft::t('Setting is required.'));
				}
			}

			if ($seedTaskModel->validate(null, false) && !$seedTaskModel->hasErrors())
			{
				try
				{
					// Run the seeding by the craft tasks service
					sproutImport()->tasks->createSeedTask($seedTaskModel);

					craft()->userSession->setNotice(Craft::t('Elements queued for seeds. Total: {tasks}', array(
						'tasks' => $quantity
					)));

					$this->redirectToPostedUrl();
				}
				catch (\Exception $e)
				{
					craft()->userSession->setError($e->getMessage());

					SproutImportPlugin::log($e->getMessage());
				}
			}
			else
			{
				$message = Craft::t('Unable to generate seeds.');

				if (empty($sets))
				{
					$message .= ' ' . Craft::t('Setting is required.');
				}
				craft()->userSession->setError($message);

				craft()->urlManager->setRouteVariables(array(
					'seeds' => $seedTaskModel
				));
			}
		}
	}
}
