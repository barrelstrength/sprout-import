<?php
namespace Craft;

class EntrySproutImportElementImporter extends BaseSproutImportElementImporter
{
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
		$sections = array(
			'single' => 'Single',
			'channel' => 'Channel'
		);

		$channels = sproutImport()->element->getChannelSections();

		return craft()->templates->render('sproutimport/settings/_entry', array(
			'id'       => $this->getName(),
			'sections' => $sections,
			'channels' => $channels
		));
	}

	public function getMockData($settings)
	{
		$sectionType = $settings['sectionType'];

		$saveIds = array();

		if (!empty($sectionType))
		{
			if ($sectionType == 'single')
			{
				$singleSection = $this->generateSingleSection();

				if ($singleSection)
				{
					$latestSection = sproutImport()->getLatestSingleSection();

					$sectionId     = $latestSection->id;
					$entryTypes    = $latestSection->getEntryTypes();
					$entryTypeId   = $entryTypes[0]->id;
					$sectionHandle = $latestSection->handle;

					$entry   = EntryRecord::model()->findByAttributes(array('sectionId' => $sectionId));
					$entryId = $entry->id;

					$entryParams = array(
						'sectionId'     => $sectionId,
						'entryTypeId'   => $entryTypeId,
						'sectionHandle' => $sectionHandle,
						'entryId'       => $entryId,
						'title'         => $latestSection->name
					);

					$id = $this->generateEntry($entryParams);
					$saveIds[] = $id;

				}
			}
			else
			{
				$channelNumber = $settings['channelNumber'];

				$sectionHandle = $settings['channel'];

				$section = craft()->sections->getSectionByHandle($sectionHandle);

				$entryTypes = $section->getEntryTypes();

				$entryParams = array(
					'sectionId'     => $section->id,
					'sectionHandle' => $section->handle
				);

				if (!empty($channelNumber))
				{
					for ($i = 1; $i <= $channelNumber; $i++)
					{
						$entryId = null;
						if (!empty($entryTypes))
						{
							// Loop all entry types for this element
							foreach ($entryTypes as $entryType)
							{
								$entryParams['entryTypeId'] = $entryType->id;

								// Update entry prevent duplicate
								if ($entryId != null)
								{
									$entryParams['entryId'] = $entryId;
								}
								else
								{
									$entryParams['entryId'] = null;
								}

								$id = $this->generateEntry($entryParams);

								$entryId = $id;

								$saveIds[] = $id;
							}
						}
					}
				}

			}
		}

		return $saveIds;
	}

	private function generateSingleSection()
	{
		$result = false;

		$faker = $this->fakerService;
		$name  = $faker->word;

		$handle = lcfirst(str_replace(' ', '', ucwords($name)));

		$settings              = array();
		$settings['name']      = $name;
		$settings['handle']    = $handle;
		$settings['type']      = SectionType::Single;
		$settings['hasUrls']   = true;
		$settings['template']  = ElementHelper::createSlug($name);
		$settings['urlFormat'] = ElementHelper::createSlug($name) . '/{slug}';

		$sectionImporter = new SectionSproutImportImporter($settings);

		$section = $sectionImporter->save();

		$findEntryType  = EntryTypeRecord::model()->findByAttributes(array('handle' => $handle));
		$entryTypeModel = EntryTypeModel::populateModel($findEntryType);

		$element = $this->getName();

		$richTextClass = new RichTextFieldSproutImport();
		$fields  = sproutImport()->element->getFieldsByType($element, $richTextClass );

		if (!empty($fields))
		{
			// Get default body field
			$fieldLayoutSettings = array
			(
				'FakeFieldContent' => array
				(
					0 => $fields[0]->id
				)
			);
			$fieldLayout         = craft()->fields->assembleLayout($fieldLayoutSettings);
			$entryTypeModel->setFieldLayout($fieldLayout);

			$result = craft()->sections->saveEntryType($entryTypeModel);

			return $result;
		}
	}

	public function generateEntry($entryParams = array())
	{

		$fakerDate = $this->fakerService->dateTimeThisYear('now');

		$data                            = array();
		$data['@model']                  = 'Entry';
		$data['attributes']['sectionId'] = $entryParams['sectionId'];
		$data['attributes']['typeId']    = $entryParams['entryTypeId'];

		$user                           = craft()->userSession->getUser();
		$data['attributes']['authorId'] = $user->id;
		$data['attributes']['locale']   = "en_us";

		$data['attributes']['postDate']    = $fakerDate;
		$data['attributes']['expiryDate']  = null;
		$data['attributes']['dateCreated'] = $fakerDate;
		$data['attributes']['dateUpdated'] = $fakerDate;
		$data['attributes']['enabled']     = true;

		$title = isset($entryParams['title']) ? $entryParams['title'] : $this->fakerService->word;

		$data['content']['title']           = $title;
		$data['content']['fields']['title'] = $title;

		$element = $this->getName();

		$fieldClasses = sproutImport()->getSproutImportFields();

		if (!empty($fieldClasses))
		{
			// Get only declared field classes
			foreach ($fieldClasses as $fieldClass)
			{
				$fields  = sproutImport()->element->getFieldsByType($element, $fieldClass);

				if (!empty($fields))
				{
					// Loop through all attach fields on this element
					foreach ($fields as $field)
					{
						$fieldHandle = $field->handle;
						$data['content']['fields'][$fieldHandle] = $fieldClass->getMockData();
					}
				}
			}
		}

		if (isset($entryParams['entryId']))
		{
			$data['content']['beforeSave']['matchBy']       = "id";
			$data['content']['beforeSave']['matchValue']    = $entryParams['entryId'];
			$data['content']['beforeSave']['matchCriteria'] = array("section" => $entryParams['sectionHandle']);
		}

		return sproutImport()->element->saveElement($data);
	}
}