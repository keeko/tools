<?php
namespace keeko\tools\generator\responder;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;
use keeko\tools\generator\responder\AbstractJsonResponderGenerator;

class PayloadJsonResponderGenerator extends AbstractJsonResponderGenerator {
	
	use PayloadGeneratorTrait;
	
	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$this->generateGetPayloadMethods($class, $this->twig->render('getPayloadMethods-skeleton.twig'));
	}
}