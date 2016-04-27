<?php
namespace keeko\tools\generator\responder;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;
use keeko\tools\model\Relationship;
use keeko\framework\utils\NameUtils;

class ToOneRelationshipJsonResponderGenerator extends AbstractModelJsonResponderGenerator {
	
	/** @var Relationship */
	private $relationship;
	
	public function __construct($service, Relationship $relationship) {
		parent::__construct($service);
		$this->relationship = $relationship;
	}

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$this->generateGetPayloadMethods($class, $this->twig->render('to-many/getPayloadMethods.twig'));
		$this->generateNotFound($class);

		// method: read(Request $request, Found $payload) : JsonResponse
		$model = $this->relationship->getModel();
		$read = $this->generatePayloadMethod('read', $this->twig->render('to-one/read.twig', [
			'class' => $model->getPhpName(),
			'related' => NameUtils::toCamelCase($this->relationship->getRelatedName())
		]), 'Found');
		
		$class->setMethod($read);
		$class->addUseStatement('keeko\\framework\\domain\\payload\\Found');
		$class->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName());
		
		// method: notUpdated(Request $request, NotUpdated $payload) : JsonResponse
		$notUpdated = $this->generatePayloadMethod('notUpdated', $this->twig->render('payload/notUpdated.twig'),
			'NotUpdated');
		$class->setMethod($notUpdated);
		$class->addUseStatement('keeko\\framework\\domain\\payload\\NotUpdated');
	}
}