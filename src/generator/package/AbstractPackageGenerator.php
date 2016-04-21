<?php
namespace keeko\tools\generator\package;

use keeko\tools\generator\AbstractCodeGenerator;
use gossi\codegen\model\PhpClass;
use keeko\framework\schema\KeekoPackageSchema;
use gossi\docblock\tags\LicenseTag;

abstract class AbstractPackageGenerator extends AbstractCodeGenerator {
	
	protected function getTemplateFolder() {
		return 'package';
	}
	
	/**
	 * Generates the class based on the package
	 * 
	 * @param KeekoPackageSchema $pkg
	 * @return PhpClass
	 */
	public function generate(KeekoPackageSchema $pkg) {
		$class = $this->generateClass($pkg);
		$class = $this->loadClass($class);
		
		$this->ensureBasicSetup($class);
		$this->addMethods($class);
		
		return $class;
	}
	
	protected function generateClass(KeekoPackageSchema $pkg) {
		$class = PhpClass::create($pkg->getClass());
		$class->setDescription($pkg->getTitle());
		
		$package = $this->packageService->getPackage();
		$docblock = $class->getDocblock();
		$docblock->appendTag(new LicenseTag($package->getLicense()));
		$this->codegenService->addAuthors($class, $package);
		
		return $class;
	}
	
	abstract protected function ensureBasicSetup(PhpClass $class);
	
	abstract protected function addMethods(PhpClass $class);

}