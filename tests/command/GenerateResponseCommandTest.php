<?php
namespace keeko\tools\tests\command;

class GenerateResponseCommandTest extends AbstractCommandTestCase {
	
	/**
	 * @expectedException DomainException
	 */
	public function testPreCheck() {
		$this->loadExample('blank');
		$this->runGenerateResponse();
	}
	
	/**
	 * @expectedException RuntimeException
	 */
	public function testNonExistingResponse() {
		$this->loadExample('module-user-action');
		$this->runGenerateResponse([
			'name' => 'unknown'
		]);
	}

	public function testNamedResponse() {
		$this->loadExample('module-user-action');
		
		$package = $this->runGenerateResponse([
			'--format' => 'json',
			'--schema' => $this->getCoreSchema(),
			'name' => 'user-create'
		]);
		
		$module = $package->getKeeko()->getModule();
		$userCreate = $module->getAction('user-create');
		
		$this->assertTrue($userCreate->hasResponse('json'));
		$this->assertEquals('keeko\\user\\response\\UserCreateJsonResponse', $userCreate->getResponse('json'));
		
		$this->assertTrue($this->root->hasChild('src/response/UserCreateJsonResponse.php'));
		$this->assertTrue($this->root->hasChild('src/response/UserResponseTrait.php'));
	}

	public function testJsonModelResponses() {
		$this->loadExample('module-user-action');

		$package = $this->runGenerateResponse([
			'--format' => 'json',
			'--schema' => $this->getCoreSchema()
		]);
		
		$actions = [
			'user-create' => 'keeko\\user\\response\\UserCreateJsonResponse',
			'user-read' => 'keeko\\user\\response\\UserReadJsonResponse',
			'user-update' => 'keeko\\user\\response\\UserUpdateJsonResponse',
			'user-delete' => 'keeko\\user\\response\\UserDeleteJsonResponse',
			'user-list' => 'keeko\\user\\response\\UserListJsonResponse',
		];
		
		$module = $package->getKeeko()->getModule();
		
		foreach ($actions as $name => $class) {
			$action = $module->getAction($name);
			$this->assertTrue($action->hasResponse('json'));
			$this->assertEquals($class, $action->getResponse('json'));
		}

		$this->assertTrue($this->root->hasChild('src/response/UserCreateJsonResponse.php'));
		$this->assertTrue($this->root->hasChild('src/response/UserReadJsonResponse.php'));
		$this->assertTrue($this->root->hasChild('src/response/UserUpdateJsonResponse.php'));
		$this->assertTrue($this->root->hasChild('src/response/UserDeleteJsonResponse.php'));
		$this->assertTrue($this->root->hasChild('src/response/UserListJsonResponse.php'));
		$this->assertTrue($this->root->hasChild('src/response/UserResponseTrait.php'));
	}
	
	public function testBlankJsonResponse() {
		$this->loadExample('module-user-action');
		
		$package = $this->runGenerateResponse([
			'--format' => 'json',
			'--schema' => $this->getCoreSchema(),
			'name' => 'password-recover'
		]);
		
		$module = $package->getKeeko()->getModule();
		$action = $module->getAction('password-recover');
		
		$this->assertTrue($action->hasResponse('json'));
		$this->assertEquals('keeko\\user\\response\\PasswordRecoverJsonResponse', $action->getResponse('json'));
		
		$this->assertTrue($this->root->hasChild('src/response/PasswordRecoverJsonResponse.php'));
		$this->assertFalse($this->root->hasChild('src/response/PasswordRecoverResponseTrait.php'));
	}
	
	public function testBlankHtmlResponse() {
		$this->loadExample('module-user-action');
	
		$package = $this->runGenerateResponse([
			'--format' => 'html',
			'--schema' => $this->getCoreSchema(),
			'name' => 'password-recover'
		]);
	
		$module = $package->getKeeko()->getModule();
		$action = $module->getAction('password-recover');
	
		$this->assertTrue($action->hasResponse('html'));
		$this->assertEquals('keeko\\user\\response\\PasswordRecoverHtmlResponse', $action->getResponse('html'));
	
		$this->assertTrue($this->root->hasChild('src/response/PasswordRecoverHtmlResponse.php'));
		$this->assertFalse($this->root->hasChild('src/response/PasswordRecoverResponseTrait.php'));
	}
	
	public function testTwigHtmlResponse() {
		$this->loadExample('module-user-action');
	
		$package = $this->runGenerateResponse([
			'--format' => 'html',
			'--template' => 'twig',
			'--schema' => $this->getCoreSchema(),
			'name' => 'password-recover'
		]);
	
		$module = $package->getKeeko()->getModule();
		$action = $module->getAction('password-recover');

		$this->assertTrue($action->hasResponse('html'));
		$this->assertEquals('keeko\\user\\response\\PasswordRecoverHtmlResponse', $action->getResponse('html'));

		$this->assertTrue($this->root->hasChild('src/response/PasswordRecoverHtmlResponse.php'));
		$this->assertFalse($this->root->hasChild('src/response/PasswordRecoverResponseTrait.php'));
	}
}
