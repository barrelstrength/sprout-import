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
		$types = array('Entry' => '', 'User' => '', 'Asset' => '', 'Category' => '', 'Tag' => '');
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
		$row = array('@model' => 'FieldModel', 'groupId' => 1, 'name' => 'Field Name' );

		$model = sproutImport()->getImporterModel($row);

		$this->assertEquals('Field', $model);

		$row = array('@model' => 'Entry', 'attributes' => 1 );

		$model = sproutImport()->getImporterModel($row);

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
}