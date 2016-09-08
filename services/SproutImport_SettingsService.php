<?php
namespace Craft;

/**
 * Class SproutImport_SettingsService
 *
 * @package Craft
 */
class SproutImport_SettingsService extends BaseApplicationComponent
{
	/**
	 * @param $postSettings
	 *
	 * @return bool
	 */
	public function saveSettings($postSettings)
	{
		$plugin         = craft()->plugins->getPlugin('sproutimport');
		$pluginSettings = $plugin->getSettings();

		if (isset($postSettings['pluginNameOverride']))
		{
			$pluginSettings['pluginNameOverride'] = $postSettings['pluginNameOverride'];
		}

		$pluginSettings = JsonHelper::encode($pluginSettings);

		$affectedRows = craft()->db->createCommand()->update('plugins', array(
			'settings' => $pluginSettings
		), array(
			'class' => 'SproutImport'
		));

		return (bool) $affectedRows;
	}
}