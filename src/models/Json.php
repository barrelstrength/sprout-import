<?php

namespace barrelstrength\sproutimport\models;

use craft\base\Model;
use Craft;

class Json extends Model
{
    /**
     * The JSON string we are handling with this model
     *
     * @var string
     */
    public $json;

    /**
     * Add JSON to this JSON model if it validates or add errors to this JSON model if it doesn't
     *
     * @param $string
     */
    public function setJson($string)
    {
        $this->validateJson($string);
    }

    /**
     * Validate our JSON to get more meaningful error messages.
     *
     * @param $string
     */
    public function validateJson($string)
    {
        // Run our string through json_decode to learn if we have errors
        json_decode($string);

        switch (json_last_error()) {
            // No errors. Add our JSON to the model.
            case JSON_ERROR_NONE:
                $this->json = $string;
                break;
            case JSON_ERROR_DEPTH:
                $this->addError('json', Craft::t('sprout-import', 'The maximum stack depth has been exceeded.'));
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $this->addError('json', Craft::t('sprout-import', 'Invalid or malformed JSON.'));
                break;
            case JSON_ERROR_CTRL_CHAR:
                $this->addError('json', Craft::t('sprout-import', 'Control character error, possibly incorrectly encoded.'));
                break;
            case JSON_ERROR_SYNTAX:
                $this->addError('json', Craft::t('sprout-import', 'Syntax error, malformed JSON.'));
                break;
            case JSON_ERROR_UTF8:
                $this->addError('json', Craft::t('sprout-import', 'Malformed UTF-8 characters, possibly incorrectly encoded.'));
                break;
            case JSON_ERROR_RECURSION:
                $this->addError('json', Craft::t('sprout-import', 'One or more recursive references in the value to be encoded.'));
                break;
            case JSON_ERROR_INF_OR_NAN:
                $this->addError('json', Craft::t('sprout-import', 'One or more NAN or INF values in the value to be encoded.'));
                break;
            case JSON_ERROR_UNSUPPORTED_TYPE:
                $this->addError('json', Craft::t('sprout-import', 'A value of a type that cannot be encoded was given.'));
                break;
            default:
                $this->addError('json', Craft::t('sprout-import', 'Unknown JSON error occurred.'));
                break;
        }
    }
}