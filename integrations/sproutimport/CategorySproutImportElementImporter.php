<?php
namespace Craft;

class CategorySproutImportElementImporter extends BaseSproutImportElementImporter
{
	/**
	 * @return mixed
	 */
	public function defineModel()
	{
		return 'CategoryModel';
	}

	/**
	 * @return bool
	 */
	public function hasSeedGenerator()
	{
		return true;
	}

	/**
	 * @return bool
	 * @throws Exception
	 * @throws \Exception
	 */
	public function save()
	{
		return craft()->categories->saveCategory($this->model);
	}

	/**
	 * @return string
	 */
	public function getSettingsHtml()
	{
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

	/**
	 * @param $settings
	 */
	public function getMockData($settings)
	{
		$categoryGroup  = $settings['categoryGroup'];
		$categoryNumber = $settings['categoryNumber'];

		$saveIds = array();

		if (!empty($categoryNumber))
		{
			for ($i = 1; $i <= $categoryNumber; $i++)
			{
				$saveIds[] = $this->generateCategory($categoryGroup);
			}
		}

		return $saveIds;
	}

	/**
	 * @param $categoryGroup
	 *
	 * @throws Exception
	 * @throws \Exception
	 */
	protected function generateCategory($categoryGroup)
	{
		$faker = $this->fakerService;
		$name  = $faker->word;

		$data                          = array();
		$data['@model']                = 'Category';
		$data['attributes']['groupId'] = $categoryGroup;
		$data['content']['title']      = $name;

		$elementName = $this->getName();

		$data['content']['fields'] = sproutImport()->seed->getMockFieldsByElementName($elementName);

		return sproutImport()->elements->saveElement($data);
	}
}