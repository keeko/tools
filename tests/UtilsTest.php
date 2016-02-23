<?php
namespace keeko\tools\tests;

use keeko\framework\schema\PackageSchema;
use keeko\tools\tests\command\AbstractCommandTestCase;
use keeko\tools\utils\NamespaceResolver;

class UtilsTest extends AbstractCommandTestCase {
	
	public function testNamespaceResolver() {
		$this->loadExample('module-user-init');

		// get namespace and path from empty package
		$package = new PackageSchema();
		$this->assertNull(NamespaceResolver::getNamespace('src', $package));
		$this->assertNull(NamespaceResolver::getSourcePath('keeko\\user', $package));
		
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
