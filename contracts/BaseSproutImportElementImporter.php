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
	 * @todo - do we need this anymore?
	 *         We now have a BaseSproutImportElementImporter class
	 *
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

	public function getElement()
	{
		$name = $this->getName();

		return craft()->elements->getElementType($name);
	}

	/**
	 * @return string
	 */
	public function populateModel($model, $settings = array())
	{
		if (isset($settings['content']['beforeSave']))
		{
			$beforeSave = $settings['content']['beforeSave'];

			$existModel = sproutImport()->element->getModelByMatches($beforeSave);

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
						$authorId               = $userModel->getAttribute('id');

						$model->setAttribute('authorId', $authorId);
					}
				}
				else
				{
					$userModel = craft()->users->getUserById($attributes['authorId']);
				}

				if ($userModel == null)
				{
					$msg = Craft::t("Invalid author value");

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
				$model->setContentFromPost($fields);

				if (isset($fields['title']))
				{
					$model->getContent()->title = $fields['title'];
				}
			}
		}

		$this->model = $model;
	}
}