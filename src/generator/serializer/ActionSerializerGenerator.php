<?php
namespace keeko\tools\generator\serializer;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;
use keeko\tools\generator\serializer\AbstractSerializerGenerator;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;

class ActionSerializerGenerator extends AbstractSerializerGenerator {
	
	public function generate(ActionSchema $action) {
		$class = new PhpClass(str_replace(['Action', 'action'], ['Serializer', 'serializer'], $action->getClass()));
		$class->setParentClassName('AbstractSerializer');
		$class->addUseStatement('keeko\\framework\\model\\AbstractSerializer');
		
		$this->generateIdentifyingMethods($class);
		
		return $class;
	}
	
	protected function generateIdentifyingMethods(PhpClass $class) {
		if (!$class->hasMethod('getType')) {
			$class->setMethod(PhpMethod::create('getType')
				->addParameter(PhpParameter::create('model'))
				->setBody($this->twig->render('getType.twig', [
					'type' => '@TODO'
				]))
			);
		}
		
		if (!$class->hasMethod('getId')) {
			$class->setMethod(PhpMethod::create('getId')
				->addParameter(PhpParameter::create('model'))
				->setBody($this->twig->render('getId.twig'))
			);
		}
	}
	
}