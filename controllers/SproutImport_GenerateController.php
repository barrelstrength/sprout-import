<?php

namespace Craft;

class SproutImport_GenerateController extends BaseController
{
	public function actionGenerateRedirectJson()
	{
		$csv = craft()->request->getPost('pastedCSV');

		if (empty($csv))
		{
			craft()->userSession->setError(Craft::t('CSV box is empty.'));

			craft()->urlManager->setRouteVariables(array(
				'csvError' => 1
			));
		}
		$json = $this->convertToJson($csv);

		if (!empty($json))
		{
			craft()->userSession->setNotice(Craft::t('CSV converted.'));

			craft()->urlManager->setRouteVariables(array(
				'json' => $json
			));
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t not convert to JSON. Make sure you input the right format.'));

			craft()->urlManager->setRouteVariables(array(
				'csvError' => 1,
				'pastedCSV' => $csv
			));
		}
	}

	private function convertToJson($csv)
	{
		$json = '';

		$array = array_map("str_getcsv", explode("\n", trim($csv)));

		if (is_array($array))
		{
			$first = $array[0];
			$first = array_map('trim', $first);

			if ($this->isHeader($first) === true)
			{
				array_shift($array);
			}
		}

		$sproutSeoImportJson = array();

		foreach ($array as $key => $attributes)
		{
			$attributes = array_map('trim', $attributes);

			if (count($attributes) == 4)
			{
				$sproutSeoImportJson[$key]['@model'] = "SproutSeo_RedirectModel";
				$sproutSeoImportJson[$key]['attributes'] = array(
					"oldUrl" => $attributes[0],
					"newUrl" => $attributes[1],
					"method" => $attributes[2],
					"regex"  => $attributes[3]
				);
			}
		}

		if (!empty($sproutSeoImportJson))
		{
			$json = json_encode($sproutSeoImportJson, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		}

		return $json;
	}

	private function isHeader($header)
	{
		$result = false;

		if (count($header) != 4)
		{
			return false;
		}

		if (
			$header[0] == 'oldUrl' ||
			$header[1] == 'newUrl' ||
			$header[2] == 'method' ||
			$header[3] == 'regex'
		)
		{
			$result = true;
		}

		return $result;
	}
}