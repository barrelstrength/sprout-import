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
		$model = $this->processUpdateElement($model, $settings);

		if (isset($settings['attributes']))
		{
			$attributes = $settings['attributes'];

			$model->setAttributes($attributes);

			// Check for email and username values if authorId attribute
			if (isset($attributes['authorId']))
			{
				if ($authorId = $this->getAuthorId($attributes['authorId']))
				{
					$model->setAttribute('authorId', $authorId);
				}
			}

			// Check if we have defaults for any unset attributes
			if (isset($settings['settings']['defaults']))
			{
				$defaults = $settings['settings']['defaults'];

				foreach ($model->getAttributes() as $attribute => $value)
				{
					if (isset($model->{$attribute}) && !$model->{$attribute})
					{
						// Check for email and username values if authorId attribute
						if ($attribute == 'authorId' && isset($defaults['authorId']))
						{
							if ($authorId = $this->getAuthorId($defaults['authorId']))
							{
								$model->setAttribute('authorId', $authorId);
							}

							continue;
						}

						if (isset($defaults[$attribute]))
						{
							$model->setAttribute($attribute, $defaults[$attribute]);
						}
					}
				}
			}

			// @todo - authorId is specific to Entry Elements, support via specific Importer
			if ((isset($attributes['authorId']) OR
					 isset($settings['settings']['defaults']['authorId'])) &&
				   empty($model['authorId']))
			{
				$message = Craft::t("Could not find Author by ID, Email, or Username.");

				SproutImportPlugin::log($message, LogLevel::Error);

				sproutImport()->addError($message, 'invalid-author');
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
					;
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
	protected function processUpdateElement($model, $settings)
	{
		if (!isset($settings['settings']['updateElement']))
		{
			return $model;
		}

		$updateElement = $settings['settings']['updateElement'];

		$element = sproutImport()->elementImporter->getModelByMatches($updateElement);

		if ($element)
		{
			return $element;
		}

		return $model;
	}

	protected function getAuthorId($authorId)
	{
		if (is_int($authorId))
		{
			$userModel = craft()->users->getUserById($authorId);
		}
		else
		{
			$userModel = craft()->users->getUserByUsernameOrEmail($authorId);
		}

		return isset($userModel) ? $userModel->id : null;
	}
}