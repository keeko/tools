<?php
namespace keeko\tools\generator\domain;

use keeko\tools\generator\AbstractCodeGenerator;
use gossi\codegen\model\PhpClass;

abstract class AbstractDomainGenerator extends AbstractCodeGenerator {
	
	protected function getTemplateFolder() {
		return 'domain';
	}
	
	protected function generateClass($className) {
		return PhpClass::create($className);
	}
	
	protected function ensureUseStatements(PhpClass $class) {
		$class->addUseStatement('keeko\\framework\\foundation\\AbstractDomain');
	}
	
	protected function ensureBasicSetup(PhpClass $class) {
		$this->ensureUseStatements($class);
		$class->setParentClassName('AbstractDomain');
	}
}