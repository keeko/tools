<?php
namespace keeko\tools\generator\package;

use keeko\tools\generator\package\AbstractPackageGenerator;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;

class ModulePackageGenerator extends AbstractPackageGenerator {
	
	protected function ensureBasicSetup(PhpClass $class) {
		$class->setParentClassName('AbstractModule');
		$class->addUseStatement('keeko\\framework\\foundation\\AbstractModule');
	}
	
	protected function addMethods(PhpClass $class) {
		// method: install()
		if (!$class->hasMethod('install')) {
			$class->setMethod(PhpMethod::create('install'));
		}
		
		// method: uninstall()
		if (!$class->hasMethod('uninstall')) {
			$class->setMethod(PhpMethod::create('uninstall'));
		}
		
		// method: update($from, $to)
		if (!$class->hasMethod('update')) {
			$class->setMethod(PhpMethod::create('update')
				->addParameter(PhpParameter::create('from')->setType('mixed'))
				->addParameter(PhpParameter::create('to')->setType('mixed'))
			);
		}
	}
	
}