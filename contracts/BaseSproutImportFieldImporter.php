<?php
namespace Craft;

abstract class BaseSproutImportFieldImporter extends BaseSproutImportImporter
{
	protected $id;

	/**
	 * Return the name of a Field from the FieldTypeModel
	 *
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
	 * Set our $this->model variable to the FieldModel Class.
	 * Our setModel() Method for Fields will always use FieldModel.
	 *
	 * @param FieldModel $model
	 *
	 * @return null
	 */
	public function setModel(FieldModel $model)
	{
		$this->model = $model;
	}

	/**
	 * Return a new FieldType model for our field
	 *
	 * @return mixed
	 */
	public function getModel()
	{
		$className = $this->getModelName() . "FieldType";

		$this->model = sproutImport()->getModelNameWithNamespace($className);

		return new $this->model;
	}

	/**
	 * Return dummy data that can be used to generate fake content for this field type
	 *
	 * @return mixed
	 */
	public abstract function getMockData();
}
