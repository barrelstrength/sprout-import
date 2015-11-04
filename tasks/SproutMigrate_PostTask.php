<?php
namespace Craft;

class SproutMigrate_PostTask extends BaseTask
{
	public function runStep($step)
	{
		craft()->config->maxPowerCaptain();
		$elements = $this->getSettings()->getAttribute('elements');
		$element  = $step ? $elements[$step] : $elements[0];

		$result = sproutMigrate()->save($element);

		return true;
	}

	public function getTotalSteps()
	{
		return count($this->getSettings()->getAttribute('elements'));
	}

	protected function defineSettings()
	{
		return array(
			'elements' => AttributeType::Mixed
		);
	}

	protected function getDescriptions()
	{
		return 'Sprout Migrate Task By Post';
	}
}
