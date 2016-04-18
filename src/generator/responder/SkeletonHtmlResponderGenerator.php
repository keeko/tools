<?php
namespace keeko\tools\generator\responder;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;

class SkeletonHtmlResponderGenerator extends AbstractHtmlResponderGenerator {

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		// method: run(Request $request, $data = null) : Response
		$class->setMethod($this->generateRunMethod($this->twig->render('skeleton-run.twig')));
	}

}