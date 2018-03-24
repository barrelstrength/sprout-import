<?php

namespace barrelstrength\sproutimport\services;

use barrelstrength\sproutbase\contracts\sproutimport\BaseImporter;
use barrelstrength\sproutimport\events\ElementImportEvent;
use Craft;
use barrelstrength\sproutimport\SproutImport;
use craft\base\Component;
use craft\base\Element;
use craft\base\Model;
use craft\helpers\ArrayHelper;

class ElementImporter extends Component
{
    /**
     * @event ElementImportEvent The event that is triggered before the element is imported
     */
    const EVENT_BEFORE_IMPORT = 'onBeforeImportElement';

    /**
     * @event ElementImportEvent The event that is triggered after the element is imported
     */
    const EVENT_AFTER_IMPORT = 'onAfterImportElement';

    /**
     * Elements saved during the length of the import job
     *
     * @var array
     */
    protected $savedElements = [];

    /**
     * Element Ids saved during the length of the import job
     *
     * @var array
     */
    protected $savedElementIds = [];

    /**
     * Current saved element when running saveElement method
     *
     * @var Element
     */
    protected $savedElement;

    /**
     * @var array
     */
    protected $unsavedElements = [];

    /**
     * @var array
     */
    protected $updatedElements = [];

    /**
     * @param              $rows
     * @param BaseImporter $importerClass
     * @param bool         $seed
     *
     * @return bool|mixed
     * @throws \ReflectionException
     */
    public function saveElement($rows, BaseImporter $importerClass, $seed = false)
    {
        $additionalDataKeys = $importerClass->getImporterDataKeys();

        $definedDataKeys = array_merge($this->getElementDataKeys(), $additionalDataKeys);

        $dataKeys = array_keys($rows);

        // Catches invalid element keys
        $dataKeysDiff = array_diff($dataKeys, $definedDataKeys);

        if (!empty($dataKeysDiff)) {
            $inputKeysText = implode(', ', $dataKeysDiff);

            $message = Craft::t('sprout-import', "Invalid element keys '$inputKeysText'.");

            SproutImport::error($message);

            SproutImport::$app->utilities->addError($inputKeysText, $message);

            return false;
        }

        $utilities = SproutImport::$app->utilities;

        $fields = $utilities->getValueByKey('content.fields', $rows);

        if (!empty($fields) && method_exists($importerClass, 'getAllFieldHandles')) {
            // Catches invalid field handles stops the importing
            $elementFieldHandles = $importerClass->getAllFieldHandles();

            // Merge default handle
            $elementFieldHandles[] = 'title';

            foreach ($fields as $fieldHandle => $fieldValue) {
                if (!in_array($fieldHandle, $elementFieldHandles)) {
                    $key = 'field-null-'.$fieldHandle;

                    $message = Craft::t('sprout-import', 'Could not find the {fieldHandle} field.', [
                        'fieldHandle' => $fieldHandle
                    ]);

                    SproutImport::error($message);
                    SproutImport::$app->utilities->addError($key, $message);

                    return false;
                }
            }
        }

        unset($rows['content']['related']);

        $model = $importerClass->getModel();
        $modelName = $importerClass->getImporterClass();

        $this->trigger(self::EVENT_BEFORE_IMPORT, new ElementImportEvent([
            'modelName' => $modelName,
            'element' => $model,
            'seed' => $seed
        ]));

        $saved = false;

        if ($model->validate(null, false) && $model->hasErrors() == false) {
            $isNewElement = !$model->id;

            try {
                try {
                    $importerClass->save();

                    // Get updated model after save
                    $model = $importerClass->getModel();

                    $errors = $model->getErrors();

                    // Check for field setting errors
                    if (!empty($errors)) {
                        $this->logErrorByModel($model);

                        return false;
                    }

                    $this->savedElement = $model;

                    $saved = true;
                } catch (\Exception $e) {
                    $message = Craft::t('sprout-import', "Error when saving Element. \n ");
                    $message .= $e->getMessage();

                    SproutImport::error($message);

                    SproutImport::$app->utilities->addError('save-importer', $message);

                    return false;
                }

                if ($saved && $isNewElement) {
                    $this->savedElementIds[] = $model->id;
                    $this->savedElements[] = $model->title;
                } elseif ($saved && !$isNewElement) {
                    $this->updatedElements[] = $model->title;
                } else {
                    $this->unsavedElements[] = $model->title;
                }
            } catch (\Exception $e) {
                $this->unsavedElements[] = [
                    'title' => $model->title,
                    'error' => $e->getMessage()
                ];

                $title = $utilities->getValueByKey('content.title', $rows);

                $fieldsMessage = is_array($fields) ? implode(', ', array_keys($fields)) : $fields;

                $message = $title.' '.$fieldsMessage.Craft::t('sprout-import', ' Check field values if it exists.');

                SproutImport::error($message);

                SproutImport::$app->utilities->addError('save-element-error', $message);

                SproutImport::$app->utilities->addError('save-element-error-message', $e->getMessage());
            }
        } else {
            $this->logErrorByModel($model);
        }

        if ($saved) {

            $this->trigger(self::EVENT_AFTER_IMPORT, new ElementImportEvent([
                'modelName' => $modelName,
                'element' => $model,
                'seed' => $seed
            ]));

            $importerClass->resolveNestedSettings($model, $rows);

            return $model;
        }

        return $saved;
    }

    /**
     * @param Model $model
     */
    public function logErrorByModel(Model $model)
    {
        SproutImport::error(Craft::t('sprout-import', 'Errors found on model while saving Element'));

        SproutImport::$app->utilities->addError('sproutImport', $model->getErrors());
    }

    /**
     * @param $model
     * @param $updateElement
     *
     * @return bool
     */
    public function getModelByMatches(Element $model, $updateElement)
    {
        $utilities = SproutImport::$app->utilities;

        if ($updateElement) {
            $matchBy = $utilities->getValueByKey('matchBy', $updateElement);
            $matchValue = $utilities->getValueByKey('matchValue', $updateElement);

            if ($matchBy && $matchValue) {
                if (is_array($matchValue)) {
                    $matchValue = $matchValue[0];

                    if (count($matchValue) > 0) {
                        $message = Craft::t('sprout-import', 'The updateElement key can only retrieve a single match. Array with multiple values was provided. Only the first value has been used to find a match: {matchValue}', [
                            'matchValue' => $matchValue
                        ]);

                        SproutImport::$app->utilities->addError('invalid-match', $message);
                    }
                }

                $elementTypeName = Craft::$app->getElements()->getElementTypeByRefHandle($model::refHandle());

                return $this->getElementByDefinitions($elementTypeName, $updateElement);
            }
        }

        return false;
    }

    /**
     * @param      $elementTypeName
     * @param      $definitions
     * @param bool $all
     *
     * @return array|bool|Element|null|static|static[]
     */
    public function getElementByDefinitions($elementTypeName, $definitions, $all = false)
    {
        $utilities = SproutImport::$app->utilities;

        $matchBy = $utilities->getValueByKey('matchBy', $definitions);
        $matchValue = $utilities->getValueByKey('matchValue', $definitions);
        $matchCriteria = $utilities->getValueByKey('matchCriteria', $definitions);

        /**
         * @var $elementType Element
         */
        $elementType = new $elementTypeName();
        $criteriaAttributes = $elementType::find()->criteriaAttributes();

        // If it is not an attribute search element by field
        if (!in_array($matchBy, $criteriaAttributes)) {

            return $elementType::find()->search(['query' => $matchBy.':'.$matchValue])->all();
        }

        $attributes = [$matchBy => $matchValue];

        if (is_array($matchCriteria)) {
            $attributes = array_merge($attributes, $matchCriteria);
        }

        try {
            if ($all == true) {
                $element = $elementType::findAll($attributes);
            } else {
                $element = $elementType::findOne($attributes);
            }

            if ($element) {
                return $element;
            }
        } catch (\Exception $e) {
            SproutImport::error($e->getMessage());

            SproutImport::$app->utilities->addError('invalid-model-match', $e->getMessage());

            return false;
        }
    }

    /**
     * @param array|null $related
     * @param array      $fields
     *
     * @return array|false
     */
    public function resolveRelationships(array $related = null, array $fields)
    {
        if (count($related)) {
            foreach ($related as $name => $definition) {
                if (empty($definition)) {
                    unset($related[$name]);
                    continue;
                }

                /**
                 * @var $importerClass BaseImporter
                 */
                $importerClass = SproutImport::$app->importers->getImporter($definition);

                if (!$importerClass) {
                    return false;
                }

                $matchBy = SproutImport::$app->utilities->getValueByKey('matchBy', $definition);
                $matchValue = SproutImport::$app->utilities->getValueByKey('matchValue', $definition);
                $newElements = SproutImport::$app->utilities->getValueByKey('newElements', $definition);

                if (!$importerClass && !$matchValue && !$matchBy) {
                    continue;
                }

                if (!is_array($matchValue)) {
                    $matchValue = ArrayHelper::toArray($matchValue);
                }

                if (!count($matchValue)) {
                    continue;
                }

                $ids = [];

                $model = $importerClass->getModel();

                $refHandle = $model::refHandle();

                $elementTypeName = Craft::$app->getElements()->getElementTypeByRefHandle($refHandle);

                $elements = $this->getElementByDefinitions($elementTypeName, $definition, true);

                if (!empty($elements)) {
                    foreach ($elements as $element) {
                        $ids[] = $element->id;
                    }
                }

                if (count($newElements) && is_array($newElements)) {
                    try {
                        foreach ($newElements as $row) {
                            $importerClass = SproutImport::$app->importers->getImporter($row);

                            $this->saveElement($row, $importerClass);

                            if ($this->savedElement) {
                                $ids[] = $this->savedElement->id;
                            }
                        }
                    } catch (\Exception $e) {
                        $message['errorMessage'] = $e->getMessage();
                        $message['errorObject'] = $e;

                        SproutImport::error($message);

                        continue;
                    }
                }

                if (count($ids)) {
                    $fields[$name] = $ids;
                } else {
                    $fields[$name] = [0];
                }
            }
        }

        return $fields;
    }

    /**
     * @param bool $returnSavedElementIds
     *
     * @return array
     */
    public function getSavedResults($returnSavedElementIds = false)
    {
        $result = [
            'saved' => count($this->savedElements),
            'updated' => count($this->updatedElements),
            'unsaved' => count($this->unsavedElements),
            'unsavedDetails' => $this->unsavedElements,
        ];

        return $returnSavedElementIds ? $this->savedElementIds : $result;
    }

    /**
     * Allows us to resolve relationships at the matrix field level
     *
     * @param $fields
     *
     * @return bool
     */
    public function resolveMatrixRelationships($fields)
    {
        foreach ($fields as $field => $blocks) {
            if (is_array($blocks) && count($blocks)) {
                foreach ($blocks as $block => $attributes) {
                    if (strpos($block, 'new') === 0 && isset($attributes['related'])) {
                        $blockFields = $attributes['fields'] ?? [];
                        $relatedFields = $attributes['related'];

                        $blockFields = $this->resolveRelationships($relatedFields, $blockFields);

                        if (!$blockFields) {
                            return false;
                        }

                        unset($fields[$field][$block]['related']);

                        if (empty($blockFields)) {
                            unset($fields[$field][$block]);
                        } else {
                            $fields[$field][$block]['fields'] = $blockFields;
                        }
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * @return array
     */
    private function getElementDataKeys()
    {
        return [
            '@model', 'attributes', 'content', 'settings'
        ];
    }
}