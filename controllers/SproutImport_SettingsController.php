<?php

namespace Craft;

class SproutImport_SettingsController extends BaseController
{
	/**
	 * Save Settings to the Database
	 *
	 * @return mixed Return to Page
	 */
	public function actionSettingsIndexTemplate()
	{
		$settingsTemplate = craft()->request->getSegment(3) ?? 'general';

		$results = craft()->db->createCommand()
			->select('settings')
			->from('plugins')
			->where('class=:class', array(':class' => 'SproutImport'))
			->queryScalar();

		$results = JsonHelper::decode($results);

		$settings = SproutImport_SettingsModel::populateModel($results);

		$this->renderTemplate('sproutimport/settings/' . $settingsTemplate, array(
			'settings' => $settings
		));
	}

	/**
	 * Save Plugin Settings
	 *
	 * @return void
	 */
	public function actionSaveSettings()
	{
		$this->requirePostRequest();
		$settings = craft()->request->getPost('settings');

		if (sproutImport()->settings->saveSettings($settings))
		{
			craft()->userSession->setNotice(Craft::t('Settings saved.'));

			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save settings.'));

			// Send the settings back to the template
			craft()->urlManager->setRouteVariables(array(
				'settings' => $settings
			));
		}
	}
}