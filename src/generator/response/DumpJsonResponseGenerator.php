<?php
namespace keeko\tools\generator\response;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;
use keeko\tools\generator\AbstractJsonResponseGenerator;

class DumpJsonResponseGenerator extends AbstractJsonResponseGenerator {

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		// method: run(Request $request, $data = null)
		$class->setMethod($this->generateRunMethod($this->twig->render('dump-run.twig')));
	}
}