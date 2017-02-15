<?php

namespace Craft;

class SproutImport_SettingsController extends BaseController
{
	/**
	 * Settings Index Template
	 *
	 * @return mixed Return to Page
	 */
	public function actionSettingsIndexTemplate()
	{
		$settingsTemplate = craft()->request->getSegment(3) ? craft()->request->getSegment(3) : 'general';

		$plugin   = craft()->plugins->getPlugin('sproutimport');
		$settings = $plugin->getSettings();

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