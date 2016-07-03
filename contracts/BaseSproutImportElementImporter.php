<?php
namespace Craft;

abstract class BaseSproutImportElementImporter extends BaseSproutImportImporter
{
	/**
	 * @return mixed
	 */
	public function getName()
	{
		return str_replace('SproutImportElementImporter', '', $this->getId());
	}

	/**
	 * @return bool
	 */
	public function isElement()
	{
		return true;
	}

	/**
	 * @param $model
	 */
	public function setModel($model)
	{
		$this->model = $model;
	}

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

	/**
	 * @return IElementType|null
	 */
	public function getElement()
	{
		$name = $this->getName();

		return craft()->elements->getElementType($name);
	}

	/**
	 * @param       $model
	 * @param array $settings
	 *
	 * @return bool|BaseElementModel|null
	 */
	public function populateModel($model, $settings = array())
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
					$msg = Craft::t("Could not find Author ID or Email.");

					sproutImport()->addError($msg, 'invalid-author');
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
						$msg = Craft::t("Unable to resolve matrix relationships.");

						$log            = array();
						$log['message'] = $msg;
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
						$msg = Craft::t("Unable to resolve related relationships.");

						$log            = array();
						$log['message'] = $msg;
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
}