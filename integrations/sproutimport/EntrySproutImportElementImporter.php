<?php
namespace Craft;

class EntrySproutImportElementImporter extends BaseSproutImportElementImporter
{
	/**
	 * @return mixed
	 */
	public function defineModel()
	{
		return 'EntryModel';
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
		return craft()->entries->saveEntry($this->model);
	}

	/**
	 * @return string
	 */
	public function getSettingsHtml()
	{
		$sections = array(
			'channel' => 'Channel'
		);

		$channels = sproutImport()->elements->getChannelSections();

		return craft()->templates->render('sproutimport/_settings/entry', array(
			'id'       => $this->getName(),
			'sections' => $sections,
			'channels' => $channels
		));
	}

	/**
	 * Generate mock data for a Channel or Structure.
	 *
	 * Singles are not supported.
	 *
	 * @param $settings
	 *
	 * @return array
	 */
	public function getMockData($quantity, $settings)
	{
		$saveIds       = array();
		$sectionHandle = $settings['channel'];

		$section    = craft()->sections->getSectionByHandle($sectionHandle);
		$entryTypes = $section->getEntryTypes();

		$entryParams = array(
			'sectionId'     => $section->id,
			'sectionHandle' => $section->handle
		);

		if (!empty($quantity))
		{
			for ($i = 1; $i <= $quantity; $i++)
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

						// Avoid duplication of saveIds
						if (!in_array($id, $saveIds) && $id !== false)
						{
							$saveIds[] = $id;
						}
					}
				}
			}
		}

		return $saveIds;
	}

	/**
	 * @param array $entryParams
	 *
	 * @return mixed
	 */
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

		$title = isset($entryParams['title']) ? $entryParams['title'] : $this->fakerService->text(60);

		$data['content']['title'] = $title;

		$elementName = $this->getName();

		$data['content']['fields'] = sproutImport()->seed->getMockFieldsByElementName($elementName);

		$data['content']['fields']['title'] = $title;

		if (isset($entryParams['entryId']))
		{
			$data['content']['beforeSave']['matchBy']       = "id";
			$data['content']['beforeSave']['matchValue']    = $entryParams['entryId'];
			$data['content']['beforeSave']['matchCriteria'] = array("section" => $entryParams['sectionHandle']);
		}

		return sproutImport()->elements->saveElement($data);
	}

	public function getAllFieldHandles()
	{
		$elementHandle = $this->model->getClassHandle();

		$fields = craft()->fields->getFieldsByElementType($elementHandle);

		$handles = array();
		if (!empty($fields))
		{
			foreach ($fields as $field)
			{
				$handles[] = $field->handle;
			}
		}

		return $handles;
	}
}