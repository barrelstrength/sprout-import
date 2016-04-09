<?php
namespace Craft;

class SproutImport_PostTask extends BaseTask
{
	/**
	 * @return string
	 */
	protected function getDescription()
	{
		return 'Sprout Import Task By Post';
	}

	/**
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'elements' => AttributeType::Mixed
		);
	}

	/**
	 * @return mixed
	 */
	public function getTotalSteps()
	{
		return count($this->getSettings()->getAttribute('elements'));
	}

	/**
	 * @param int $step
	 *
	 * @return bool
	 */
	public function runStep($step)
	{
		craft()->config->maxPowerCaptain();
		$elements = $this->getSettings()->getAttribute('elements');
		$element  = $step ? $elements[$step] : $elements[0];

		$result = sproutImport()->save($element);

		return true;
	}
}
