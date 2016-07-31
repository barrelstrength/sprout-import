<?php
namespace Craft;

class MatrixSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getFieldTypeModelName()
	{
		return 'MatrixFieldType';
	}

	/**
	 * @return bool
	 */
	public function canMockData()
	{
		return true;
	}

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

				$fieldLayouts = craft()->fields->getLayoutFieldsById($fieldLayoutId);

				$values[$key]['fields'] = sproutImport()->mockData->getMockFields($fieldLayouts);

				$count++;
			}
		}

		return $values;
	}

}
