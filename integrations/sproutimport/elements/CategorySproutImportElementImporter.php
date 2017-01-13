<?php
namespace Craft;

class CategorySproutImportElementImporter extends BaseSproutImportElementImporter
{
	private $categoryGroup;

	/**
	 * @return mixed
	 */
	public function getModelName()
	{
		return 'Category';
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

		return craft()->templates->render('sproutimport/_integrations/category/settings', array(
			'id'             => $this->getModelName(),
			'categoryGroups' => $groupsSelect
		));
	}

	/**
	 * @param $settings
	 */
	public function getMockData($quantity, $settings)
	{
		$saveIds       = array();
		$categoryGroup = $settings['categoryGroup'];

		if (!empty($quantity))
		{
			for ($i = 1; $i <= $quantity; $i++)
			{
				$model = $this->generateCategory($categoryGroup);
				$saveIds[] = $model->id;
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

		$this->categoryGroup = $categoryGroup;

		$fieldLayouts = $this->getFieldLayoutsByGroupId();

		$data['content']['fields'] = sproutImport()->mockData->getFieldsWithMockData($fieldLayouts);

		return sproutImport()->elementImporter->saveElement($data);
	}

	private function getFieldLayoutsByGroupId()
	{
		$groupId = $this->categoryGroup;

		$categoryGroup = craft()->categories->getGroupById($groupId);

		$fieldLayoutId = $categoryGroup->fieldLayoutId;

		$fieldLayouts = craft()->fields->getLayoutFieldsById($fieldLayoutId);

		return $fieldLayouts;
	}
}