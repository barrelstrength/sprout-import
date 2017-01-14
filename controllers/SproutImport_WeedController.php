<?php
namespace Craft;

class SproutImport_WeedController extends BaseController
{
	/**
	 * Weed Index Template
	 *
	 * @throws HttpException
	 */
	public function actionWeedIndexTemplate()
	{
		$seeds = sproutImport()->seed->getSeeds();

		$this->renderTemplate('sproutimport/weeds', array(
			'seeds' => $seeds
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
			if (sproutImport()->seed->weed($class))
			{
				craft()->userSession->setNotice(Craft::t('The garden is weeded!'));

				$this->redirectToPostedUrl();
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
				if (sproutImport()->seed->weed($class, true))
				{
					craft()->userSession->setNotice(Craft::t('Data Kept!'));

					$this->redirectToPostedUrl();
				}
				else
				{
					craft()->userSession->setError(Craft::t('Unable to keep data. Try again.'));
				}
			}
		}
	}

	public function actionProcessWeed()
	{
		$submit = craft()->request->getPost('submit');

		$isKeep = true;

		if ($submit == 'Weed' || $submit == 'Weed All')
		{
			$isKeep = false;
		}

		$seeds = array();

		$idsString  = craft()->request->getPost('ids');

		if ($idsString != null && $idsString != '*')
		{
			$ids = explode(',', $idsString);

			$seeds = sproutImport()->seed->getSeedsByIds($ids);
		}

		if ($idsString == '*')
		{
			$seeds = sproutImport()->seed->getAllSeeds();
		}

		if (!empty($seeds))
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

			foreach ($seeds as $seed)
			{
				try
				{
					if (!$isKeep)
					{
						// we're just appending 'Model' and adding it to the array here...
						$row['@model'] = $seed['importerClass'] . 'Model';

						$modelName = sproutImport()->getImporterModelName($row);
						$importer = sproutImport()->getImporterByModelName($modelName, $row);
						$importer->deleteById($seed['itemId']);
					}

					sproutImport()->seed->deleteSeedById($seed['id']);
				}
				catch (\Exception $e)
				{
					SproutImportPlugin::log($e->getMessage());
				}
			}

			if ($transaction && $transaction->active)
			{
				$transaction->commit();
			}
		}
	}
}
