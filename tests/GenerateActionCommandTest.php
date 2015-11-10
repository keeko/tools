<?php
namespace keeko\tools\tests;

class GenerateActionCommandTest extends AbstractCommandTestCase {
	
	public function testCreateSpecificActionForModel() {
		$this->loadExample('module-init');
		
		$package = $this->runGenerateAction([
			'--model' => 'user',
			'--type' => 'create',
			'--title' => 'Create a user',
			'--schema' => __DIR__ . '/../core/database/schema.xml',
			'name' => 'user-create'
		]);
		
		$module = $package->getKeeko()->getModule();
		$this->assertTrue($module->getActionNames()->contains('user-create'));
		$this->assertTrue($this->root->hasChild('src/action/UserCreateAction.php'));
		$this->assertTrue($this->root->hasChild('src/action/base/UserCreateActionTrait.php'));

		$action = $module->getAction('user-create');
		$this->assertEquals('user-create', $action->getName());
		$this->assertEquals('keeko\\user\\action\\UserCreateAction', $action->getClass());
		$this->assertEquals([], $action->getAcl()->toArray());
	}
	
	public function testCreateSpecificModel() {
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
		$this->assertEquals([], $action->getAcl()->toArray());
	}
}
