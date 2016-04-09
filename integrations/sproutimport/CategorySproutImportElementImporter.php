<?php
namespace Craft;

class CategorySproutImportElementImporter extends BaseSproutImportElementImporter
{
	public function getModel()
	{
		$model = 'Craft\\CategoryModel';

		return new $model;
	}

	public function save()
	{
		return craft()->categories->saveCategory($this->model);
	}

	public function getMockSettings()
	{
		$variables = array();

		$groupsSelect = array();

		$groups = craft()->categories->getAllGroups();

		if (!empty($groups))
		{
			foreach ($groups as $group)
			{
				$groupsSelect[$group->id]['label'] = $group->name;
				$groupsSelect[$group->id]['value'] = $group->id;
			}
		}

		return craft()->templates->render('sproutimport/_settings/category', array(
			'id'             => $this->getName(),
			'categoryGroups' => $groupsSelect
		));
	}

	public function getMockData($settings)
	{
		$categoryGroup  = $settings['categoryGroup'];
		$categoryNumber = $settings['categoryNumber'];

		if (!empty($categoryNumber))
		{
			for ($i = 1; $i <= $categoryNumber; $i++)
			{
				$this->generateCategory($categoryGroup);
			}
		}
	}

	private function generateCategory($categoryGroup)
	{
		$faker = $this->fakerService;
		$name  = $faker->word;

		$category          = new CategoryModel();
		$category->groupId = $categoryGroup;
		$category->enabled = true;
		$category->locale  = 'en_us';
		$category->slug    = ElementHelper::createSlug($name);

		$category->getContent()->title = $name;

		$result = craft()->categories->saveCategory($category);
	}
}