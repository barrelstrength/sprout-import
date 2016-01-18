<?php
namespace Craft;

class SproutImport_PostTask extends BaseTask
{
	public function runStep($step)
	{
		craft()->config->maxPowerCaptain();
		$elements = $this->getSettings()->getAttribute('elements');
		$element  = $step ? $elements[$step] : $elements[0];

		$result = sproutImport()->save($element);

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
		return 'Sprout Import Task By Post';
	}
}
