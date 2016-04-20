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
	
	public function generate(KeekoPackageSchema $pkg) {
		$class = $this->generateClass($pkg);
		$class = $this->loadClass($class);
		
		$this->ensureBasicSetup($class);
		$this->addMethods($class);
	}
	
	protected function generateClass(KeekoPackageSchema $pkg) {
		$class = PhpClass::create($pkg->getClass());
		$class->setDescription($pkg->getTitle());
			
		$docblock = $class->getDocblock();
		$docblock->appendTag(new LicenseTag($this->package->getLicense()));
		$this->codegenService->addAuthors($class, $this->package);
		
		return $class;
	}
	
	abstract protected function ensureBasicSetup(PhpClass $class);
	
	abstract protected function addMethods(PhpClass $class);

}