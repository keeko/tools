<?php
namespace keeko\tools\generator;

use gossi\codegen\model\PhpClass;
use keeko\core\schema\ActionSchema;

class BlankJsonResponseGenerator extends AbstractJsonResponseGenerator {

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		// method: run(Request $request, $data = null)
		$class->setMethod($this->generateRunMethod($this->twig->render('blank-run.twig')));
	}
}