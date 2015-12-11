<?php
namespace keeko\tools\tests\command;

class GenerateApiCommandTest extends AbstractCommandTestCase {
	
	/**
	 * @expectedException DomainException
	 */
	public function testPreCheck() {
		$this->loadExample('blank');
		$this->runGenerateApi([
			'--schema' => $this->getCoreSchema()
		]);
	}
	
// 	/**
// 	 * expectedException DomainException
// 	 */
// 	public function testNoCoreModule() {
// 		$this->loadExample('module-blabla-init');
// 		$this->runGenerateAction([
// 			'--schema' => $this->getCoreSchema()
// 		]);
// 	}
	
	public function testUserModule() {
		$this->loadExample('module-user-response');
		
		$this->runGenerateApi([
			'--schema' => $this->getCoreSchema()
		]);
		
		$this->assertTrue($this->root->hasChild('api.json'));
	}
}
