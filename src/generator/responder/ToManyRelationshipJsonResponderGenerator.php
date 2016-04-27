<?php
namespace keeko\tools\generator\responder;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;
use keeko\framework\utils\NameUtils;
use keeko\tools\model\ManyRelationship;
use keeko\tools\services\CommandService;

class ToManyRelationshipJsonResponderGenerator extends AbstractModelJsonResponderGenerator {
	
	/** @var ManyRelationship */
	private $relationship;
	
	public function __construct(CommandService $service, ManyRelationship $relationship) {
		parent::__construct($service);
		$this->relationship = $relationship;
	}

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$this->generateGetPayloadMethods($class, $this->twig->render('to-many/getPayloadMethods.twig'));
		$this->generateNotValid($class);
		$this->generateNotFound($class);
		
		// method: updated(Request $request, Updated $payload) : JsonResponse
		$model = $this->relationship->getModel();
		$updated = $this->generatePayloadMethod('updated', $this->twig->render('to-many/updated.twig', [
			'class' => $model->getPhpName(),
			'related' => NameUtils::pluralize(NameUtils::toCamelCase($this->relationship->getRelatedName()))
		]), 'Updated');
		
		$class->setMethod($updated);
		$class->addUseStatement('keeko\\framework\\domain\\payload\\Updated');
		$class->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName());
		
		// method: notUpdated(Request $request, NotUpdated $payload) : JsonResponse
		$notUpdated = $this->generatePayloadMethod('notUpdated', $this->twig->render('payload/notUpdated.twig'),
			'NotUpdated');
		$class->setMethod($notUpdated);
		$class->addUseStatement('keeko\\framework\\domain\\payload\\NotUpdated');
	}
}