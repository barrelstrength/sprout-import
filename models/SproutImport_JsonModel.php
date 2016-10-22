<?php
namespace Craft;

class SproutImport_JsonModel extends BaseModel
{
	/**
	 * SproutImport_JsonModel constructor.
	 *
	 * @param mixed|null $string
	 */
	public function __construct($string)
	{
		$this->setJson($string);

		parent::__construct($string);
	}

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'path' => AttributeType::String,
			'json' => AttributeType::Mixed
		);
	}

	/**
	 * @param $string
	 */
	public function setJson($string)
	{
		$this->validateJson($string);
	}

	/**
	 * @param $string
	 */
	public function validateJson($string)
	{
		$result = json_decode($string);

		switch (json_last_error())
		{
			// No errors. Add our JSON to the model.
			case JSON_ERROR_NONE:
				$this->json = $result;
				break;
			case JSON_ERROR_DEPTH:
				$this->addError('json', Craft::t('The maximum stack depth has been exceeded.'));
				break;
			case JSON_ERROR_STATE_MISMATCH:
				$this->addError('json', Craft::t('Invalid or malformed JSON.'));
				break;
			case JSON_ERROR_CTRL_CHAR:
				$this->addError('json', Craft::t('Control character error, possibly incorrectly encoded.'));
				break;
			case JSON_ERROR_SYNTAX:
				$this->addError('json', Craft::t('Syntax error, malformed JSON.'));
				break;
			case JSON_ERROR_UTF8:
				$this->addError('json', Craft::t('Malformed UTF-8 characters, possibly incorrectly encoded.'));
				break;
			case JSON_ERROR_RECURSION:
				$this->addError('json', Craft::t('One or more recursive references in the value to be encoded.'));
				break;
			case JSON_ERROR_INF_OR_NAN:
				$this->addError('json', Craft::t('One or more NAN or INF values in the value to be encoded.'));
				break;
			case JSON_ERROR_UNSUPPORTED_TYPE:
				$this->addError('json', Craft::t('A value of a type that cannot be encoded was given.'));
				break;
			default:
				$this->addError('json', Craft::t('Unknown JSON error occurred.'));
				break;
		}
	}
}