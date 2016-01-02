<?php
namespace keeko\tools\tests\command;

class InitCommandTest extends AbstractCommandTestCase {
	
	public function testBlankInitApp() {
		$package = $this->runInit([
			'--name' => 'keeko/test',
			'--type' => 'app',
			'--description' => 'Test description',
			'--author' => 'Tester',
			'--license' => 'MIT',
			'--title' => 'Test Package'
		]);
		
		$this->assertTrue($this->root->hasChild('src/TestApplication.php'));

		// Composer Values
		$this->assertEquals('keeko/test', $package->getFullName());
		$this->assertEquals('keeko-app', $package->getType());
		$this->assertEquals('Test description', $package->getDescription());
		$this->assertEquals('MIT', $package->getLicense());
		$this->assertEquals('Tester', $package->getAuthors()->get(0)->getName());

		// Keeko Values
		$keeko = $package->getKeeko();
		$app = $keeko->getApp();
		$this->assertEquals('Test Package', $app->getTitle());
		$this->assertEquals('keeko\\test\\TestApplication', $app->getClass());
		
		// compare files
		// source code
		$this->assertEqualsExample('app-init/src/TestApplication.php', $this->root->getChild('src/TestApplication.php')->url());
		
		// composer.json
		$this->assertEqualsExample('app-init/composer.json', $this->root->getChild('composer.json')->url());
	}

	public function testBlankInitModule() {
		$package = $this->runInit([
			'--name' => 'keeko/user',
			'--type' => 'module',
			'--description' => 'Test description',
			'--author' => 'Tester',
			'--license' => 'MIT',
			'--title' => 'Keeko User Module'
		]);
		
		$this->assertTrue($this->root->hasChild('src/UserModule.php'));
		
		// Composer Values
		$this->assertEquals('keeko/user', $package->getFullName());
		$this->assertEquals('keeko-module', $package->getType());
		$this->assertEquals('Test description', $package->getDescription());
		$this->assertEquals('MIT', $package->getLicense());
		$this->assertEquals('Tester', $package->getAuthors()->get(0)->getName());
		
		// Keeko Values
		$keeko = $package->getKeeko();
		$module = $keeko->getModule();
		$this->assertEquals('Keeko User Module', $module->getTitle());
		$this->assertEquals('keeko\\user\\UserModule', $module->getClass());
		$this->assertEquals('user', $module->getSlug());

		// compare files
		// source code
		$this->assertEqualsExample('module-user-init/src/UserModule.php', $this->root->getChild('src/UserModule.php')->url());

		// composer.json
		$this->assertEqualsExample('module-user-init/composer.json', $this->root->getChild('composer.json')->url());
	}

	public function testNamespaceOption() {
		$package = $this->runInit([
			'--name' => 'keeko/test',
			'--type' => 'app',
			'--description' => 'Test description',
			'--author' => 'Tester',
			'--license' => 'MIT',
			'--title' => 'Test Package',
			'--namespace' => 'gossi\\test'
		]);
	
		// Keeko Values
		$keeko = $package->getKeeko();
		$app = $keeko->getApp();
		$this->assertEquals('gossi\\test\\TestApplication', $app->getClass());
	}

	public function testSecondRun() {
		$this->loadExample('module-blabla-init');
		$package = $this->runInit();
		$requireDev = $package->getRequireDev();
		
		$this->assertTrue($requireDev->has('puli/composer-plugin'));
		$this->assertEquals('@beta', $requireDev->get('puli/composer-plugin'));
	}
}
