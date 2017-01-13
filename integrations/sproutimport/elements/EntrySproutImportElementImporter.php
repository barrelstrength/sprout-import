<?php
namespace Craft;

class EntrySproutImportElementImporter extends BaseSproutImportElementImporter
{
	private $entryTypes;

	/**
	 * @return mixed
	 */
	public function getModelName()
	{
		return 'Entry';
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
		try
		{
			$sectionId = $this->model->sectionId;

			if (craft()->sections->getSectionById($sectionId) == null)
			{
				$message = Craft::t("Invalid section ID");

				SproutImportPlugin::log($message, LogLevel::Error);

				sproutImport()->addError($message, 'invalid-section');

				return false;
			}

			return craft()->entries->saveEntry($this->model);
		}
		catch (\Exception $e)
		{
			SproutImportPlugin::log($e->getMessage(), LogLevel::Error);

			sproutImport()->addError($e->getMessage(), 'invalid-entry-model');

			return false;
		}
	}

	/**
	 * @return string
	 */
	public function getSettingsHtml()
	{
		$sections = array(
			'channel' => 'Channel'
		);

		$channels = $this->getChannelSections();

		return craft()->templates->render('sproutimport/_integrations/entry/settings', array(
			'id'       => $this->getModelName(),
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

		$this->entryTypes = $entryTypes;

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
					$randomEntryType = $entryTypes[array_rand($entryTypes)];

					$entryParams['entryTypeId'] = $randomEntryType->id;

					// Update entry prevent duplicate
					if ($entryId != null)
					{
						$entryParams['entryId'] = $entryId;
					}
					else
					{
						$entryParams['entryId'] = null;
					}

					$model = $this->generateEntry($entryParams);

					if (isset($model->id))
					{
						$id = $model->id;
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

		$fieldLayouts = $this->getFieldLayouts();

		$data['content']['fields'] = sproutImport()->mockData->getFieldsWithMockData($fieldLayouts);

		$data['content']['fields']['title'] = $title;

		if (isset($entryParams['entryId']))
		{
			$data['settings']['updateElement']['matchBy']       = "id";
			$data['settings']['updateElement']['matchValue']    = $entryParams['entryId'];
			$data['settings']['updateElement']['matchCriteria'] = array("section" => $entryParams['sectionHandle']);
		}

		return sproutImport()->elementImporter->saveElement($data);
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

	private function getFieldLayouts()
	{
		$entryTypes = $this->entryTypes;

		$fieldLayouts = array();

		if (!empty($entryTypes))
		{
			foreach ($entryTypes as $entryType)
			{
				$fieldLayoutId = $entryType->fieldLayoutId;

				$layouts = craft()->fields->getLayoutFieldsById($fieldLayoutId);

				$fieldLayouts = array_merge($fieldLayouts, $layouts);
			}
		}

		return $fieldLayouts;
	}

	/**
	 * @return array
	 */
	public function getChannelSections()
	{
		$selects  = array();
		$sections = craft()->sections->getAllSections();
		if (!empty($sections))
		{
			foreach ($sections as $section)
			{
				if ($section->type == 'single')
				{
					continue;
				}
				$selects[$section->handle] = $section->name;
			}
		}

		return $selects;
	}
}