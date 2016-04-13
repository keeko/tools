<?php
namespace keeko\tools\generator\responder;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;

class ModelDeleteJsonResponderGenerator extends AbstractModelJsonResponderGenerator {

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$this->generateGetPayloadMethods($class, $this->twig->render('getPayloadMethods-delete.twig'));
		$this->generateNotFound($class);
		
		// method: deleted(Request $request, PayloadInterface $payload)
		$deleted = $this->generatePayloadMethod('deleted', $this->twig->render('model-deleted.twig'));
		$class->setMethod($deleted);
		
		// method: notDeleted(Request $request, PayloadInterface $payload)
		$notDeleted = $this->generatePayloadMethod('notDeleted', $this->twig->render('model-notDeleted.twig'));
		$class->setMethod($notDeleted);
	}
}