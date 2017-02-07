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
}