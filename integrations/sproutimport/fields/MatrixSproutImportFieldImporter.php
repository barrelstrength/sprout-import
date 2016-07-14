<?php
namespace Craft;

class MatrixSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->fieldModel->settings;

		$fieldId = $this->fieldModel->id;

		$blocks = craft()->matrix->getBlockTypesByFieldId($fieldId);

		$limit = 5;

		$values = array();

		if (!empty($blocks))
		{
			$count = 1;

			foreach ($blocks as $block)
			{
				$key = 'new' . $count;

				$values[$key] = array(
					'type' => $block->handle,
					'enabled' => 1
				);

				$fieldLayoutId = $block->fieldLayoutId;

				$fields = craft()->fields->getLayoutFieldsById($fieldLayoutId);

				if (!empty($fields))
				{
					$handles = array();

					foreach ($fields as $field)
					{
						$fieldHandle = $field->field->handle;
						$fieldType   = $field->field->type;
						$handles[] = $fieldType;
						$fieldClass = sproutImport()->getFieldImporterClassByType($fieldType);

						if ($fieldClass != null)
						{
							$fieldClass->setField($field->field);

							$values[$key]['fields'][$fieldHandle] = $fieldClass->getMockData();
						}
					}
				}

				$count++;
			}
		}

		return $values;
	}

}
