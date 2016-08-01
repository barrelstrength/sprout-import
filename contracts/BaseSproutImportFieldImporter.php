<?php
namespace Craft;

abstract class BaseSproutImportFieldImporter extends BaseSproutImportImporter
{
	protected $id;

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->getModel()->getName();
	}

	/**
	 * @return bool
	 */
	public function isField()
	{
		return true;
	}

	/**
	 * Our setModel() Method for Fields will always use the FieldModel Class
	 *
	 * @param FieldModel $model
	 */
	public function setModel(FieldModel $model)
	{
		$this->model = $model;
	}

	/**
	 * @return mixed
	 */
	public function getModel()
	{
		$className = $this->getModelName() . "FieldType";

		$this->model = sproutImport()->getModelNameWithNamespace($className);

		return new $this->model;
	}
	/**
	 * @return mixed
	 */
	public abstract function getMockData();
}
