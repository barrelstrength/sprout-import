<?php
namespace Craft;

require CRAFT_BASE_PATH . 'vendor/autoload.php';

use \Mockery as m;

class SproutImportBaseTest extends BaseTest
{

	/**
	 * @var \Mockery\MockInterface
	 */
	protected $config;

	/**
	 * ENVIRONMENT
	 * -----------
	 */
	public function setUp()
	{
		$this->autoload();

		$this->config = m::mock('Craft\ConfigService');

		$this->config->shouldReceive('usePathInfo')->andReturn(true);
		$this->config->shouldReceive('getIsInitialized')->andReturn(true);
		$this->config->shouldReceive('omitScriptNameInUrls')->andReturn(true);

		$this->config->shouldReceive('get')->with('user', 'db')->andReturn('root');
		$this->config->shouldReceive('get')->with('password', 'db')->andReturn('secret');
		$this->config->shouldReceive('get')->with('database', 'db')->andReturn('sandboxdev');
		$this->config->shouldReceive('get')->with('devMode')->andReturn(false);
		$this->config->shouldReceive('get')->with('cpTrigger')->andReturn('admin');
		$this->config->shouldReceive('get')->with('baseCpUrl')->andReturn('http://sandbox.dev/');
		$this->config->shouldReceive('get')->with('pageTrigger')->andReturn('p');
		$this->config->shouldReceive('get')->with('actionTrigger')->andReturn('action');
		$this->config->shouldReceive('get')->with('usePathInfo')->andReturn(true);
		$this->config->shouldReceive('get')->with('translationDebugOutput')->andReturn(false);

		$this->config->shouldReceive('getLocalized')->with('loginPath')->andReturn('login');
		$this->config->shouldReceive('getLocalized')->with('logoutPath')->andReturn('logout');
		$this->config->shouldReceive('getLocalized')->with('setPasswordPath')->andReturn('setpassword');
		$this->config->shouldReceive('getLocalized')->with('siteUrl')->andReturn('http://sandbox.dev');

		$this->config->shouldReceive('getCpLoginPath')->andReturn('login');
		$this->config->shouldReceive('getCpLogoutPath')->andReturn('logout');
		$this->config->shouldReceive('getCpSetPasswordPath')->andReturn('setpassword');
		$this->config->shouldReceive('getResourceTrigger')->andReturn('resource');

		$this->config->shouldReceive('get')->with('slugWordSeparator')->andReturn('-');
		$this->config->shouldReceive('get')->with('allowUppercaseInSlug')->andReturn(false);
		$this->config->shouldReceive('get')->with('addTrailingSlashesToUrls')->andReturn(true);

		$this->setComponent(craft(), 'config', $this->config);

		$mainService = new SproutImportService();
		$seedService = new SproutImport_SeedService();

		$this->setComponent(craft(), 'sproutImport_seed', $seedService);

		$plugin = new SproutImportPlugin();

		$mainService->init();
		$this->setComponent(craft(), 'sproutImport', $mainService);

		$pluginService = m::mock('Craft\PluginsService[getPlugin]');
		$pluginService->shouldReceive('getPlugin')->with('sproutimport')->andReturn($plugin);

		$this->setComponent(craft(), 'plugins', $pluginService);
	}

	public function tearDown()
	{
		m::close();
	}

	protected function autoload()
	{
		$map = array(
			'\\Craft\\SproutImportPlugin'       => '../SproutImportPlugin.php',
			'\\Craft\\SproutImportService'      => '../services/SproutImportService.php',
			'\\Craft\\SproutImport_SeedService' => '../services/SproutImport_SeedService.php',
			'\\Craft\\BaseSproutImportImporter' => '../contracts/BaseSproutImportImporter.php',
			'\\Craft\\FieldSproutImportImporter' => '../integrations/sproutimport/FieldSproutImportImporter.php'
		);

		foreach ($map as $classPath => $filePath)
		{
			if (!class_exists($classPath, false))
			{
				require_once $filePath;
			}
		}
	}
}
