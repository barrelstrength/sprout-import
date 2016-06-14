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
		$obj1 = new \stdClass;
		$obj1->id = 11;

		$obj2 = new \stdClass;
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

		$expected = array("Content%20One" => array(11,22));

		$this->assertEquals($expected, $entryFields);
	}

	public function testGetModelNameWithNamespace()
	{
		$name = "FieldModel";

		$modelName = sproutImport()->getModelNameWithNamespace($name);

		$expected = "Craft\\FieldModel";
		$this->assertEquals($expected, $modelName);

		$name = "Craft\\FieldModel";

		$modelName = sproutImport()->getModelNameWithNamespace($name);

		$expected = "Craft\\FieldModel";
		$this->assertEquals($expected, $modelName);
	}

	public function testGetRandomArray()
	{
		$values = array();
		$values[] = array('value' => 'one');
		$values[] = array('value' => 'two');
		$values[] = array('value' => 'three');
		$values[] = array('value' => 'four');

		$length = count($values);
		$number = rand(1, $length);

		$randArrays = sproutImport()->getRandomArrays($values, $number);

		$randCount = count($randArrays);

		$this->assertEquals($number, $randCount);

		$oneArray = sproutImport()->getRandomArrays($values, 1);

		$isArray = is_array($oneArray);

		$this->assertTrue($isArray);

		$keys = array(1, 3);

		$options = sproutImport()->getOptionValuesByKeys($keys, $values);

		$expected = array('two', 'four');

		$this->assertEquals($expected, $options);
	}
}