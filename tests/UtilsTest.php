<?php
namespace keeko\tools\tests;

use keeko\core\schema\PackageSchema;
use keeko\tools\utils\NamespaceResolver;
use keeko\tools\tests\command\AbstractCommandTestCase;

class UtilsTest extends AbstractCommandTestCase {
	
	public function testNamespaceResolver() {
		$this->loadExample('module-user-init');

		// get namespace
		$package = PackageSchema::fromFile($this->root->getChild('composer.json')->url());
		
		$this->assertEquals('keeko\\user', NamespaceResolver::getNamespace('src', $package));
		$this->assertEquals('keeko\\user', NamespaceResolver::getNamespace('src/', $package));
		$this->assertEquals('keeko\\user\\action', NamespaceResolver::getNamespace('src/action', $package));
		$this->assertEquals('keeko\\user\\action', NamespaceResolver::getNamespace('src/action/', $package));
		$this->assertEquals('keeko\\user\\action\\base', NamespaceResolver::getNamespace('src/action/base', $package));
		$this->assertEquals('keeko\\user\\action\\base', NamespaceResolver::getNamespace('src/action/base/', $package));
		
		// get path
		$this->assertEquals('src/', NamespaceResolver::getSourcePath('keeko\\user', $package));
		$this->assertEquals('src/', NamespaceResolver::getSourcePath('keeko\\user\\', $package));
		$this->assertEquals('src/action/', NamespaceResolver::getSourcePath('keeko\\user\\action', $package));
		$this->assertEquals('src/action/', NamespaceResolver::getSourcePath('keeko\\user\\action\\', $package));
		$this->assertEquals('src/action/base/', NamespaceResolver::getSourcePath('keeko\\user\\action\\base', $package));
		$this->assertEquals('src/action/base/', NamespaceResolver::getSourcePath('keeko\\user\\action\\base\\', $package));
	}
}
