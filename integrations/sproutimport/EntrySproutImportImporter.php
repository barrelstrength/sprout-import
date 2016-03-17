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
				Craft::dd($singleSection);
			}
		}
		$data = array();

		$data['@model'] = 'Entry';
		$data['attributes']['sectionId']  = 2;
		$data['attributes']['typeId']     = 2;
		$data['attributes']['authorId']   = 2;
		$data['attributes']['locale']     = "en_us";
		$data['attributes']['slug']       = "modi-et-in-libero-sint-quaerat";
		$data['attributes']['postDate']   = "2014-04-15 11:04:28";
		$data['attributes']['expiryDate'] = null;
		$data['attributes']['dateCreated'] = "2014-04-15 11:04:28";
		$data['attributes']['dateUpdated'] = "2014-04-15 11:04:28";
		$data['attributes']['enabled']     = true;

		$data['content']['title'] = "Quia sapiente eum aut neque dolor.";
		$data['content']['fields']['title']      = "Quia sapiente eum aut neque dolor.";
		$data['content']['fields']['body']       = "Quia sapiente eum aut neque dolor.";
		$data['content']['fields']['categories'] = [];

		return $data;
	}

	private function generateSingleSection()
	{
		$faker = \Faker\Factory::create();
		$faker->addProvider(new \Faker\Provider\Lorem($faker));

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

		return $section;
	}
}