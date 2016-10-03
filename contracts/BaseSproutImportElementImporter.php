<?php
namespace Craft;

/**
 * Class BaseSproutImportElementImporter
 *
 * @package Craft
 */
abstract class BaseSproutImportElementImporter extends BaseSproutImportImporter
{
	/**
	 * @inheritdoc BaseSproutImportImporter::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		$model = $this->getModel();

		if (!is_object($model))
		{
			return $model . Craft::t(" Model definition not found.");
		}

		$elementTypeName = $model->getElementType();
		$elementType     = craft()->elements->getElementType($elementTypeName);

		return $elementType->getName();
	}

	/**
	 * @return bool
	 */
	public function isElement()
	{
		return true;
	}

	/**
	 * @return mixed
	 */
	public function getElement()
	{
		$name = $this->getModelName() . "Model";

		return craft()->elements->getElementType($name);
	}

	/**
	 * @param       $model
	 * @param array $settings
	 *
	 * @return BaseElementModel|null
	 */
	public function setModel($model, $settings = array())
	{
		$model = $this->processBeforeSave($model, $settings);

		if (isset($settings['attributes']))
		{
			$attributes = $settings['attributes'];

			$model->setAttributes($attributes);

			// Allows author email to add as author of the entry
			if (isset($attributes['authorId']))
			{
				if (is_array($attributes['authorId']) && !empty($attributes['authorId']['email']))
				{
					$userEmail = $attributes['authorId']['email'];
					$userModel = craft()->users->getUserByUsernameOrEmail($userEmail);

					if ($userModel != null)
					{
						$authorId = $userModel->getAttribute('id');

						$model->setAttribute('authorId', $authorId);
					}
				}
				else
				{
					$userModel = craft()->users->getUserById($attributes['authorId']);
				}

				if ($userModel == null && isset($attributes['defaultAuthorId']))
				{
					$defaultAuthorId = $attributes['defaultAuthorId'];

					$userModel = craft()->users->getUserById($defaultAuthorId);

					$authorId = $userModel->getAttribute('id');

					$model->setAttribute('authorId', $authorId);
				}

				if ($userModel == null)
				{
					$message = Craft::t("Could not find Author ID or Email.");

					SproutImportPlugin::log($message, LogLevel::Error);

					sproutImport()->addError($message, 'invalid-author');
				}
			}
		}

		if (isset($settings['content']))
		{
			$model->setContent($settings['content']);

			if (isset($settings['content']['fields']))
			{
				$fields = $settings['content']['fields'];

				if (!empty($fields))
				{
					$fields = sproutImport()->elementImporter->resolveMatrixRelationships($fields);

					if (!$fields)
					{
						$message['error']  = Craft::t("Unable to resolve matrix relationships.");
						$message['fields'] = $fields;

						SproutImportPlugin::log($message);
					}
				}

				// @todo - when trying to import Sprout Forms Form Models,
				// which do not have any fields or content, running this method kills the script
				// moving the $related check to before the method runs, works.
				if (isset($settings['content']['related']) && count($settings['content']['related']))
				{
					$related = $settings['content']['related'];

					$fields = sproutImport()->elementImporter->resolveRelationships($related, $fields);

					if (!$fields)
					{
						$message['error']  = Craft::t("Unable to resolve related relationships.");
						$message['fields'] = $fields;

						SproutImportPlugin::log($message);
					}
				}

				$model->setContentFromPost($fields);

				if (isset($fields['title']))
				{
					$model->getContent()->title = $fields['title'];
				}
			}
		}

		$this->model = $model;

		return $this->model;
	}

	/**
	 * @return string
	 */
	abstract public function save();

	/**
	 * Delete an Element using the Element ID
	 *
	 * @param $id
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function deleteById($id)
	{
		return craft()->elements->deleteElementById($id);
	}

	/**
	 * Determine if we have any elements we should handle before handling the current Element
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	protected function processBeforeSave($model, $settings)
	{
		if (!isset($settings['content']['beforeSave']))
		{
			return $model;
		}

		$beforeSave = $settings['content']['beforeSave'];

		$element = sproutImport()->elementImporter->getModelByMatches($beforeSave);

		if ($element)
		{
			return $element;
		}

		return $model;
	}
}