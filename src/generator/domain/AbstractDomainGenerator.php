<?php
namespace keeko\tools\generator\domain;

use gossi\codegen\model\PhpClass;
use keeko\tools\generator\AbstractGenerator;

abstract class AbstractDomainGenerator extends AbstractGenerator {

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