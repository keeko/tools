<?php
namespace keeko\tools\tests;

class GenerateActionCommandTest extends AbstractCommandTestCase {
	
	public function testCreateActionByModel() {
		$this->loadExample('module-init');
		
		$package = $this->runGenerateAction([
			'--model' => 'user',
			'--title' => 'Create a user',
			'--acl' => 'guest, user, admin', // test acls as well
			'--classname' => 'keeko\\user\\action\\CreateUserAction',
			'--schema' => __DIR__ . '/../core/database/schema.xml',
			'name' => 'user-create'
		]);
		
		$module = $package->getKeeko()->getModule();
		$this->assertTrue($module->getActionNames()->contains('user-create'));
		$this->assertTrue($this->root->hasChild('src/action/CreateUserAction.php'));
		$this->assertTrue($this->root->hasChild('src/action/base/CreateUserActionTrait.php'));

		$action = $module->getAction('user-create');
		$this->assertEquals('user-create', $action->getName());
		$this->assertEquals('keeko\\user\\action\\CreateUserAction', $action->getClass());
		$this->assertEquals(['guest', 'user', 'admin'], $action->getAcl()->toArray());
	}
	
	public function testCreateModelByParam() {
		$this->loadExample('module-init');
	
		$package = $this->runGenerateAction([
			'--model' => 'user',
			'--schema' => __DIR__ . '/../core/database/schema.xml'
		]);

		$module = $package->getKeeko()->getModule();
		$this->assertEquals(5, $module->getActionNames()->size());
		$this->assertTrue($module->getActionNames()->contains('user-create'));
		$this->assertTrue($this->root->hasChild('src/action/UserCreateAction.php'));
		$this->assertTrue($this->root->hasChild('src/action/base/UserCreateActionTrait.php'));

		$action = $module->getAction('user-create');
		$this->assertEquals('user-create', $action->getName());
		$this->assertEquals('keeko\\user\\action\\UserCreateAction', $action->getClass());
		$this->assertEquals(['admin'], $action->getAcl()->toArray());
	}
	
	public function testCreateModelByType() {
		$this->loadExample('module-init');
	
		$package = $this->runGenerateAction([
			'--type' => 'create',
			'--model' => 'user',
			'--schema' => __DIR__ . '/../core/database/schema.xml'
		]);
	
		$module = $package->getKeeko()->getModule();
		$this->assertEquals(1, $module->getActionNames()->size());
		$this->assertTrue($module->getActionNames()->contains('user-create'));
		$this->assertTrue($this->root->hasChild('src/action/UserCreateAction.php'));
		$this->assertTrue($this->root->hasChild('src/action/base/UserCreateActionTrait.php'));
	
		$action = $module->getAction('user-create');
		$this->assertEquals('user-create', $action->getName());
		$this->assertEquals('keeko\\user\\action\\UserCreateAction', $action->getClass());
		$this->assertEquals(['admin'], $action->getAcl()->toArray());
	}
	
	public function testCreateCoreModel() {
		$this->loadExample('module-init');
	
		$package = $this->runGenerateAction([
			'--schema' => __DIR__ . '/../core/database/schema.xml'
		]);
	
		$module = $package->getKeeko()->getModule();
		$this->assertEquals(5, $module->getActionNames()->size());
		$this->assertTrue($module->getActionNames()->contains('user-create'));
		$this->assertTrue($this->root->hasChild('src/action/UserCreateAction.php'));
		$this->assertTrue($this->root->hasChild('src/action/base/UserCreateActionTrait.php'));
	
		$action = $module->getAction('user-create');
		$this->assertEquals('user-create', $action->getName());
		$this->assertEquals('keeko\\user\\action\\UserCreateAction', $action->getClass());
		$this->assertEquals(['admin'], $action->getAcl()->toArray());
	}
	
	/**
	 * @expectedException RuntimeException
	 */
	public function testMissingTitle() {
		$this->loadExample('module-init');
		$this->runGenerateAction([
			'--model' => 'user',
// 			'--title' => 'Create a user',
			'--schema' => __DIR__ . '/../core/database/schema.xml',
			'name' => 'user-create'
		]);
	}
	
	public function testBlankAction() {
		$this->loadExample('module-init');
		
		$this->runGenerateAction([
			'--title' => 'Resets the password',
			'--classname' => 'keeko\\user\\action\\ResetPasswordAction',
			'--acl' => 'guest',
			'name' => 'reset-pw'
		]);
		
		$this->assertTrue($this->root->hasChild('src/action/ResetPasswordAction.php'));
		$this->assertEqualsFixture('ResetPasswordAction.php', $this->root->getChild('src/action/ResetPasswordAction.php')->url());
	}
}
