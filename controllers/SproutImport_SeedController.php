<?php
namespace Craft;

class SproutImport_SeedController extends BaseController
{
	public function actionWeed()
	{
		$type = craft()->request->getPost('type');

		if (!craft()->sproutImport_seed->weed($type))
		{
			craft()->userSession->setError(Craft::t('No luck weeding. Try again.'));
		}
		else
		{
			craft()->userSession->setNotice(Craft::t('The garden is weeded!'));
		}
	}
}
