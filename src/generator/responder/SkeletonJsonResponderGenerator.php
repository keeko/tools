<?php
namespace keeko\tools\generator\responder;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;

class SkeletonJsonResponderGenerator extends AbstractJsonResponderGenerator {

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		// method: run(Request $request, $data = null)
		$class->addUseStatement('Tobscure\\JsonApi\\Resource');
		$class->addUseStatement('Tobscure\\JsonApi\\Document');
		$class->setMethod($this->generateRunMethod($this->twig->render('skeleton-run.twig')));
	}
}