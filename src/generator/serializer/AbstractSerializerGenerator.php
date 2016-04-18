<?php
namespace keeko\tools\generator\serializer;

use keeko\tools\generator\AbstractCodeGenerator;
use gossi\codegen\model\PhpClass;

class AbstractSerializerGenerator extends AbstractCodeGenerator {
	
	protected function getTemplateFolder() {
		return 'serializer';
	}
	
	protected function generateClass($className) {
		return PhpClass::create($className);
	}
	
	protected function ensureUseStatements(PhpClass $class) {
		$class->addUseStatement('keeko\\framework\\model\\AbstractSerializer');
	}
	
	protected function ensureBasicSetup(PhpClass $class) {
		$this->ensureUseStatements($class);
		$class->setParentClassName('AbstractSerializer');
	}

}