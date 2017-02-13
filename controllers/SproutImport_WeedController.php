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

		$this->renderTemplate('sproutimport/weed', array(
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

		$dateSubmitted  = craft()->request->getPost('dateSubmitted');

		if ($dateSubmitted != null && $dateSubmitted != '*')
		{
			$seeds = sproutImport()->seed->getSeedsByDateSubmitted($dateSubmitted);
		}

		if ($dateSubmitted == '*')
		{
			$seeds = sproutImport()->seed->getAllSeeds();
		}

		if (sproutImport()->seed->weed($seeds, $isKeep))
		{
			craft()->userSession->setError(Craft::t('The garden is weeded.'));

			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Unable to weed data.'));
		}
	}
}
