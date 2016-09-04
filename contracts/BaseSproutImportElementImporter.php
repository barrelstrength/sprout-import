<?php
namespace Craft;

abstract class BaseSproutImportElementImporter extends BaseSproutImportImporter
{
	/**
	 * @return mixed
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
	 * @return IElementType|null
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
	 * @return bool|BaseElementModel|null
	 */
	public function setModel($model, $settings = array())
	{
		if (isset($settings['content']['beforeSave']))
		{
			$beforeSave = $settings['content']['beforeSave'];

			$existModel = sproutImport()->elements->getModelByMatches($beforeSave);

			if ($existModel)
			{
				$model = $existModel;
			}
		}

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
					$fields = sproutImport()->elements->resolveMatrixRelationships($fields);

					if (!$fields)
					{
						$message = Craft::t("Unable to resolve matrix relationships.");

						$log            = array();
						$log['message'] = $message;
						$log['fields']  = $fields;

						sproutImport()->log($log, 'invalid-matrix');
					}
				}

				// @todo - when trying to import Sprout Forms Form Models,
				// which do not have any fields or content, running this method kills the script
				// moving the $related check to before the method runs, works.
				if (isset($settings['content']['related']) && count($settings['content']['related']))
				{
					$related = $settings['content']['related'];

					$fields = sproutImport()->elements->resolveRelationships($related, $fields);

					if (!$fields)
					{
						$message = Craft::t("Unable to resolve related relationships.");

						$log            = array();
						$log['message'] = $message;
						$log['fields']  = $fields;

						sproutImport()->log($log, 'invalid-relation');
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
	 * @param $id
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function deleteById($id)
	{
		return craft()->elements->deleteElementById($id);
	}
}