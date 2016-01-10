<?php
namespace Craft;

class SproutMigrate_SeedController extends BaseController
{
	public function actionWeed()
	{
		if (!craft()->sproutMigrate_seed->weed())
		{
			craft()->userSession->setError(Craft::t('No luck weeding. Try again.'));
		}
		else
		{
			craft()->userSession->setNotice(Craft::t('The garden is weeded!'));
		}
	}
}
