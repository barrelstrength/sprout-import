<?php
namespace Craft;

/**
 * Class BaseSproutImportImporter
 *
 * @package Craft
 */
abstract class BaseSproutImportImporter
{
	/**
	 * The model of the thing being imported: Element, Setting, Field etc.
	 *
	 * Examples:
	 * - UserModel
	 * - FieldModel
	 * - PlainTextFieldType
	 *
	 * @var
	 */
	public $model;

	/**
	 * The model of the importer class.
	 *
	 * Examples:
	 * - UserSproutImportElementImporter
	 * - FieldSproutImportSettingsImporter
	 * - PlainTextSproutImportFieldImporter
	 *
	 * @var null
	 */
	protected $importerClass = null;

	/**
	 * Any data an importer needs to store and access at another time such as
	 * after something is saved and another action needs to be performed
	 *
	 * @var
	 */
	protected $data;

	/**
	 * ???
	 *
	 * @var array
	 */
	protected $rows;

	/**
	 * Any errors that have occurred that we want to store and access later
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Access to the Faker Service layer
	 *
	 * @var null
	 */
	protected $fakerService;

	/**
	 * BaseSproutImportImporter constructor.
	 *
	 * @param array $rows
	 * @param null  $fakerService
	 */
	public function __construct($rows = array(), $fakerService = null)
	{
		$this->rows = $rows;

		if (count($rows))
		{
			$model = $this->getModel();

			$this->setModel($model, $rows);
		}

		if ($fakerService == null)
		{
			$this->fakerService = sproutImport()->faker->getGenerator();
		}
		else
		{
			$this->fakerService = $fakerService;
		}
	}

	/**
	 * The Importer Class
	 *
	 * Examples:
	 * - Craft\UserSproutImportElementImporter
	 * - Craft\FieldSproutImportSettingsImporter
	 * - Craft\PlainTextSproutImportFieldImporter
	 *
	 * @return mixed
	 */
	final public function getImporterClass()
	{
		$importerClass = str_replace('Craft\\', '', get_class($this));

		$this->importerClass = $importerClass;

		return $importerClass;
	}

	/**
	 * The user-friendly name for the imported data type
	 *
	 * Examples:
	 * - Users
	 * - Fields
	 * - Plain Text
	 *
	 * @return mixed
	 */
	abstract public function getName();

	/**
	 * The primary model that the Importer supports
	 *
	 * Examples:
	 * - UserModel => User
	 * - FieldModel => Field
	 * - PlainTextFieldType => PlainText
	 * - SproutForms_FormModel => SproutForms_Form
	 *
	 * @return mixed
	 */
	abstract public function getModelName();

	/**
	 * @return bool
	 */
	public function isElement()
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function isSettings()
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function isField()
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function hasSeedGenerator()
	{
		return false;
	}

	/**
	 * @param $model
	 */
	public function setModel($model, $settings = array())
	{
		if (count($settings))
		{
			$model->setAttributes($settings);
		}

		$this->model = $model;

		return $this->model;
	}

	/**
	 * Get a model of the thing being imported, and assign it to $this->model
	 *
	 * Examples:
	 * - new UserModel
	 * - new FieldModel
	 * - new PlainTextFieldType
	 *
	 * @return mixed
	 */
	public function getModel()
	{
		if (!$this->model)
		{
			$className = $this->getModelName() . "Model";
			$model     = sproutImport()->getModelNameWithNamespace($className);

			if (!class_exists($model))
			{
				$this->addError($model . ' not found.', 'not-found');

				return $model;
			}

			$this->model = new $model;
		}

		return $this->model;
	}

	/**
	 * @return bool
	 */
	public function resolveRelatedSettings()
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function resolveNestedSettings()
	{
		return true;
	}

	/**
	 * @return string
	 */
	public function getSettingsHtml()
	{
		return "";
	}

	/**
	 * @return string
	 */
	public function getSeedCount()
	{
		$name = $this->getModelName();

		$count = sproutImport()->seed->getSeedCountByElementType($name);

		return $count;
	}

	/**
	 * @param $data
	 */
	public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * Define the keys available in $this->data
	 *
	 * @return array
	 */
	public function getImporterDataKeys()
	{
		return array();
	}

	/**
	 * Return any errors from the model of the thing being imported
	 *
	 * Examples:
	 * - $userModel->getErrors()
	 * - $fieldModel->getErrors()
	 * - $plainTextFieldModel->getErrors()
	 *
	 * @return mixed
	 */
	public function getModelErrors()
	{
		return $this->model->getErrors();
	}

	/**
	 * Add an error to global errors array: $this->errors
	 *
	 * @param      $message
	 * @param bool $key
	 */
	public function addError($message, $key = false)
	{
		if ($key)
		{
			$this->errors[$key] = $message;
		}
		else
		{
			$this->errors[] = $message;
		}
	}

	/**
	 * Retrieve an error from global errors array: $this->errors
	 *
	 * @param $key
	 *
	 * @return string|false
	 */
	public function getError($key)
	{
		$error = (isset($this->errors[$key])) ? $this->errors[$key] : false;

		return $error;
	}

	/**
	 * Retrieve all errors from global errors array: $this->errors
	 *
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Returns whether any errors exist in the global errors array: $this->errors
	 *
	 * @return bool
	 */
	public function hasErrors()
	{
		$hasErrors = (!empty($this->errors)) ? true : false;

		return $hasErrors;
	}

	/**
	 * Reset global errors array and remove all errors
	 */
	public function clearErrors()
	{
		$this->errors = array();
	}
}
