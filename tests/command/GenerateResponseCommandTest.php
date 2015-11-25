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

	public function testNamedResponse() {
		$this->loadExample('module-user-action');
		
		$package = $this->runGenerateResponse([
			'--format' => 'json',
			'--schema' => $this->getCoreSchema(),
			'name' => 'user-create'
		]);
	}
}