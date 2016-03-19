<?php

namespace Craft;
class EntrySproutImportImporter extends ElementSproutImportImporter
{

	public function isElement()
	{
		return true;
	}

	public function getModel()
	{
		$model = 'Craft\\EntryModel';
		return new $model;
	}

	public function save()
	{
		return craft()->entries->saveEntry($this->model);
	}

	public function getMockSettings()
	{

		$sections   = array('single' => 'Single', 'channel' => 'Channel');

		return craft()->templates->render('sproutimport/settings/_entry', array(
			'id'       => $this->getName(),
			'sections' => $sections,
			'channels' => sproutImport()->element->getChannelSections()
		));
	}

	public function getMockData($settings)
	{
		$faker = \Faker\Factory::create();

		$sectionType = $settings['sectionType'];

		if (!empty($sectionType))
		{
			if ($sectionType == 'single')
			{
				$singleSection = $this->generateSingleSection();

				if($singleSection)
				{
					$latestSection = sproutImport()->getLatestSingleSection();

					$sectionId = $latestSection->id;

					$entryTypes = $latestSection->getEntryTypes();
					$typeId = $entryTypes[0]->id;

					$entry = EntryRecord::model()->findByAttributes(array('sectionId' => $latestSection->id));
					$entryId = $entry->id;
				}

			}
		}
		$data = array();

		$data['@model'] = 'Entry';

		$fakerDate = $this->fakerService->dateTimeThisYear($max = 'now');

		$data['attributes']['sectionId']  = $sectionId;
		$data['attributes']['typeId']     = $typeId;
		$data['attributes']['authorId']   = 2;
		$data['attributes']['locale']     = "en_us";
		//$data['attributes']['slug']       = "modi-et-in-libero-sint-quaerat";
		$data['attributes']['postDate']   = $fakerDate;
		$data['attributes']['expiryDate'] = null;
		$data['attributes']['dateCreated'] = $fakerDate;
		$data['attributes']['dateUpdated'] = $fakerDate;
		$data['attributes']['enabled']     = true;

	//	$data['content']['title'] = "Quia sapiente eum aut neque dolor.";
		//$data['content']['fields']['title']      = "Quia sapiente eum aut neque dolor.";
		$data['content']['fields']['body']       = $this->fakerService->paragraph();

		$data['content']['beforeSave']['matchBy']    = "id";
		$data['content']['beforeSave']['matchValue'] = $entryId;
		$data['content']['beforeSave']['matchCriteria'] = array("section" => $latestSection->handle);

		return sproutImport()->element->saveElement($data);
	}

	private function generateSingleSection()
	{
		$result = false;

		$faker = $this->fakerService;
		$name = $faker->word;

		$handle = lcfirst(str_replace(' ', '', ucwords($name)));

		$settings = array();
		$settings['name']      = $name;
		$settings['handle']    = $handle;
		$settings['type']      = SectionType::Single;
		$settings['hasUrls']   = true;
		$settings['template']  = ElementHelper::createSlug($name);
		$settings['urlFormat'] = ElementHelper::createSlug($name) . '/{slug}';

		$sectionImporter = new SectionSproutImportImporter($settings);

		$section = $sectionImporter->save();

		$findEntryType = EntryTypeRecord::model()->findByAttributes(array('handle' => $handle));
		$entryTypeModel = EntryTypeModel::populateModel($findEntryType);

		// Get default body field
		$fieldLayoutSettings = array
		(
			'Content' => array
			(
				0 => '1'
			)
		);
		$fieldLayout = craft()->fields->assembleLayout($fieldLayoutSettings);
		$entryTypeModel->setFieldLayout($fieldLayout);

		$result = craft()->sections->saveEntryType($entryTypeModel);

		return $result;
	}
}