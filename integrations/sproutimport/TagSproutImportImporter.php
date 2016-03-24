<?php
namespace Craft;

class TagSproutImportImporter extends SproutImportBaseElementImporter
{
	public function getModel()
	{
		$model = 'Craft\\TagModel';

		return new $model;
	}

	public function save()
	{
		return craft()->tags->saveTag($this->model);
	}

	public function getMockSettings()
	{
		$variables = array();

		$groupsSelect = array();

		$groups = craft()->tags->getAllTagGroups();

		if (!empty($groups))
		{
			foreach ($groups as $group)
			{
				$groupsSelect[$group->id]['label'] = $group->name;
				$groupsSelect[$group->id]['value'] = $group->id;
			}
		}

		return craft()->templates->render('sproutimport/settings/_tag', array(
			'id'        => $this->getName(),
			'tagGroups' => $groupsSelect
		));
	}

	public function getMockData($settings)
	{
		$tagGroup  = $settings['tagGroup'];
		$tagNumber = $settings['tagNumber'];

		if (!empty($tagNumber))
		{
			for ($i = 1; $i <= $tagNumber; $i++)
			{
				$this->generateTag($tagGroup);
			}
		}
	}

	private function generateTag($tagGroup)
	{
		$faker = $this->fakerService;
		$name  = $faker->word;

		$tag          = new TagModel();
		$tag->groupId = $tagGroup;
		$tag->enabled = true;
		$tag->locale  = 'en_us';
		$tag->slug    = ElementHelper::createSlug($name);

		$tag->getContent()->title = $name;

		$result = craft()->tags->saveTag($tag);
	}
}