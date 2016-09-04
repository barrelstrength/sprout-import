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
