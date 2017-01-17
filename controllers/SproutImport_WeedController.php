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

		$elementImporters = sproutImport()->getSproutImportImporters();

		craft()->templates->includeCssResource('sproutimport/css/sproutimport.css');

		$this->renderTemplate('sproutimport/weeds', array(
			'seeds' => $seeds
		));
	}

	public function actionProcessWeed()
	{
		$this->requirePostRequest();

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

		sproutImport()->seed->weed($seeds, $isKeep);

	}
}
