<?php

namespace Craft;


class SproutImport_FakerService extends BaseApplicationComponent
{

	private $lib;

	public function __construct()
	{
		require_once dirname(__FILE__) . '/../vendor/autoload.php';

		$this->lib = \Faker\Factory::create();

	}

	public function getGenerator()
	{
		return $this->lib;
	}

}