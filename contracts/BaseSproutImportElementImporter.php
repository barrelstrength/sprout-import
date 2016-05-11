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
}