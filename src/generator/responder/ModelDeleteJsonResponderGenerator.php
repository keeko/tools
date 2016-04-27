<?php
namespace keeko\tools\generator\responder;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;

class ModelDeleteJsonResponderGenerator extends AbstractModelJsonResponderGenerator {

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$this->generateGetPayloadMethods($class, $this->twig->render('model/getPayloadMethods-delete.twig'));
		$this->generateNotFound($class);
		
		// method: deleted(Request $request, Deleted $payload) : JsonResponse
		$deleted = $this->generatePayloadMethod('deleted', $this->twig->render('payload/deleted.twig'), 
			'Deleted');
		$class->setMethod($deleted);
		$class->addUseStatement('keeko\\framework\\domain\\payload\\Deleted');
		
		// method: notDeleted(Request $request, NotDeleted $payload) : JsonResponse
		$notDeleted = $this->generatePayloadMethod('notDeleted', $this->twig->render('payload/notDeleted.twig'),
			'NotDeleted');
		$class->setMethod($notDeleted);
		$class->addUseStatement('keeko\\framework\\domain\\payload\\NotDeleted');
	}
}