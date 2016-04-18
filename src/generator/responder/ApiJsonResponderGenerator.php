<?php
namespace keeko\tools\generator\responder;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;

class ApiJsonResponderGenerator extends AbstractJsonResponderGenerator {

	protected $serializer;

	/**
	 * @param string $serializer
	 */
	public function setSerializer($serializer) {
		$this->serializer = $serializer;
	}
	
	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$serializer = new PhpClass($this->serializer);

		// method: run(Request $request, $data = null) : JsonResponse
		$class->addUseStatement($serializer->getQualifiedName());
		$class->setMethod($this->generateRunMethod($this->twig->render('api-run.twig', [
			'serializer' => $serializer->getName()
		])));
	}
}