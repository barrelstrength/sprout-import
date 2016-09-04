<?php
namespace Craft;

class MatrixSproutImportFieldImporter extends BaseSproutImportFieldImporter
{
	/**
	 * @return string
	 */
	public function getModelName()
	{
		return 'Matrix';
	}

	/**
	 * @return mixed
	 */
	public function getMockData()
	{
		$settings = $this->model->settings;

		$fieldId = $this->model->id;

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
					'type'    => $block->handle,
					'enabled' => 1
				);

				$fieldLayoutId = $block->fieldLayoutId;

				$fieldLayouts = craft()->fields->getLayoutFieldsById($fieldLayoutId);

				$values[$key]['fields'] = sproutImport()->mockData->getFieldsWithMockData($fieldLayouts);

				$count++;
			}
		}

		return $values;
	}

}
