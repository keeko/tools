<?php
namespace keeko\tools\generator\package;

use keeko\tools\generator\package\AbstractPackageGenerator;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;

class AppPackageGenerator extends AbstractPackageGenerator {

	protected function ensureBasicSetup(PhpClass $class) {
		$class->setParentClassName('AbstractApplication');
		$class->addUseStatement('keeko\\framework\\foundation\\AbstractApplication');
	}
	
	protected function addMethods(PhpClass $class) {
		// method: run(Request $request, $path)
		if (!$class->hasMethod('run')) {
			$class->setMethod(PhpMethod::create('run')
				->addParameter(PhpParameter::create('request')->setType('Request'))
				->addParameter(PhpParameter::create('path')->setType('string'))
			);
			$class->addUseStatement('Symfony\\Component\\HttpFoundation\\Request');
		}
	}
}