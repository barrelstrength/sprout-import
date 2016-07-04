<?php
namespace Craft;

require 'SproutImportBaseTest.php';

use \Mockery as m;

class SproutImportServiceTest extends SproutImportBaseTest
{
	public function testServicesIsInitializedAndTestsCanBeRan()
	{
		$this->assertInstanceOf('\\Craft\\SproutImportService', sproutImport());
	}

	public function testIsElementType()
	{
		$types           = array('Entry' => '', 'User' => '', 'Asset' => '', 'Category' => '', 'Tag' => '');
		$elementsService = m::mock('Craft\ElementsService')
			->shouldReceive('getAllElementTypes')
			->andReturn($types)
			->mock();

		sproutImport()->init($elementsService);

		$return = sproutImport()->isElementType('Entry');
		$this->assertTrue($return);

		$return = sproutImport()->isElementType('SentEmail');
		$this->assertFalse($return);
	}

	public function testGetImporterModel()
	{
		// Accepts with suffix Model or without
		$row = array('@model' => 'FieldModel', 'groupId' => 1, 'name' => 'Field Name');

		$names = array('Field', 'Entry');

		$model = sproutImport()->getImporterModel($row, $names);

		$this->assertEquals('Field', $model);

		$row = array('@model' => 'Entry', 'attributes' => 1);

		$model = sproutImport()->getImporterModel($row, $names);

		$this->assertEquals('Entry', $model);
	}

	public function testGetModelExist()
	{
		$fieldModel = new \Craft\FieldModel;

		$settings = array('handle' => 'test');

		$importer = m::mock('Craft\FieldSproutImportImporter[getObjectByHandle]')
			->shouldReceive('setSettings')
			->andReturn($settings)
			->shouldReceive('getObjectByHandle')
			->andReturn(null)
			->mock();

		$model = $importer->getModel();

		$this->assertInstanceOf('\Craft\FieldModel', $model);
	}

	public function testGetFieldIdsByHandle()
	{
		$obj1     = new \stdClass;
		$obj1->id = 11;

		$obj2     = new \stdClass;
		$obj2->id = 22;

		$fieldsService = m::mock('Craft\FieldsService')
			->shouldReceive('getFieldByHandle')
			->andReturn($obj1, $obj2)
			->mock();

		$fields = array(
			"blogField1",
			"body"
		);

		$name = "Content One";

		$entryFields = sproutImport()->getFieldIdsByHandle($name, $fields, $fieldsService);

		$expected = array("Content%20One" => array(11, 22));

		$this->assertEquals($expected, $entryFields);
	}

	public function namespaceProvider()
	{
		return array(
			array("FieldModel", "Craft\\FieldModel"),
			array("Craft\\FieldModel", "Craft\\FieldModel")
		);
	}

	/**
	 * @dataProvider namespaceProvider
	 */
	public function testGetModelNameWithNamespace($option, $expected)
	{
		$modelName = sproutImport()->getModelNameWithNamespace($option);

		$this->assertEquals($expected, $modelName);
	}

	public function testGetRandomArray()
	{
		$values   = array();
		$values[] = array('value' => 'one');
		$values[] = array('value' => 'two');
		$values[] = array('value' => 'three');
		$values[] = array('value' => 'four');

		$length = count($values);
		$number = rand(1, $length);

		$randArrays = sproutImport()->seed->getRandomArrays($values, $number);

		$randCount = count($randArrays);

		$this->assertEquals($number, $randCount);

		$oneArray = sproutImport()->seed->getRandomArrays($values, 1);

		$isArray = is_array($oneArray);

		$this->assertTrue($isArray);

		$keys = array(1, 3);

		$options = sproutImport()->seed->getOptionValuesByKeys($keys, $values);

		$expected = array('two', 'four');

		$this->assertEquals($expected, $options);
	}

	public function minutesProvider()
	{
		return array(
			array(array('time' => strtotime("12:16 PM"), 'increment' => 15), "12:15 PM"),
			array(array('time' => strtotime("12:35 AM"), 'increment' => 15), "12:30 AM"),
			array(array('time' => strtotime("8:25 PM"), 'increment' => 30), "8:00 PM"),
			array(array('time' => strtotime("8:34 AM"), 'increment' => 30), "8:30 AM"),
			array(array('time' => strtotime("5:44 PM"), 'increment' => 60), "5:00 PM"),
			array(array('time' => strtotime("5:14 AM"), 'increment' => 60), "5:00 AM"),
		);
	}

	/**
	 *
	 * @dataProvider minutesProvider
	 */
	public function testGetMinutesByIncrement($option, $expected)
	{
		$convertedTime = sproutImport()->seed->getMinutesByIncrement($option['time'], $option['increment']);

		$this->assertEquals($expected, $convertedTime);
	}

	public function testGenerateRandomColumns()
	{
		$columns = array();

		$columns = array(
			'col1' => array
			(
				'heading' => 'column one',
				'handle'  => 'columnOne',
				'width'   => '',
				'type'    => 'singleline'
			),
			'col2' => array
			(
				'heading' => 'column two',
				'handle'  => 'columnTwo',
				'width'   => '',
				'type'    => 'singleline'
			),
			'col3' => array
			(
				'heading' => 'column three',
				'handle'  => 'columnThree',
				'width'   => '',
				'type'    => 'singleline'
			),
			'col4' => array
			(
				'heading' => 'column four',
				'handle'  => 'columnFour',
				'width'   => '',
				'type'    => 'singleline'
			),
			'col5' => array
			(
				'heading' => 'column five',
				'handle'  => 'columnFive',
				'width'   => '',
				'type'    => 'singleline'
			)
		);

		$values = sproutImport()->seed->generateColumns($columns);

		$expected = 5;
		$length   = count($values);

		$this->assertEquals($expected, $length);

		$tableKeys = array_keys($values);

		$expected = array('col1', 'col2', 'col3', 'col4', 'col5');
		$this->assertEquals($expected, $tableKeys);
	}

	public function testSeedElements()
	{
		$settings = array
		(
			'sources'        => array
			(
				0 => 'section:33',
				1 => 'section:2'
			),
			'limit'          => '4',
			'selectionLabel' => 'Add an entry'
		);

		$sources = $settings['sources'];

		$find = sproutImport()->seed->getElementGroupIds($sources);

		$expected = array(33, 2);

		$this->assertEquals($expected, $find);

		$allSources = "*";
		$expected = "*";

		$find = sproutImport()->seed->getElementGroupIds($allSources);

		$this->assertEquals($expected, $find);
	}
}