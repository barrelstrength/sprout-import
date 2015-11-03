<?php
namespace Craft;

class SproutMigratePostTask extends BaseTask
{
	public function runStep($step)
	{
		craft()->config->maxPowerCaptain();

		$elements = $this->getSettings()->getAttribute('elements');

		return false;
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
