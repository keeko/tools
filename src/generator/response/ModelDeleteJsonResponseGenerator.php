<?php
namespace keeko\tools\generator\response;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;
use keeko\tools\generator\AbstractModelJsonResponseGenerator;

class ModelDeleteJsonResponseGenerator extends AbstractModelJsonResponseGenerator {

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		
		// method: run(Request $request, $data = null)
		$class->setMethod($this->generateRunMethod($this->twig->render('delete-run.twig')));
	}
}