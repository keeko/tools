<?php
namespace keeko\tools\generator\responder;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;
use Propel\Generator\Model\Table;

class ToOneRelationshipJsonResponderGenerator extends AbstractPayloadJsonResponderGenerator {
	
	/** @var Table */
	private $model;
	
	/** @var Table */
	private $foreign;
	
	public function __construct($service, Table $model, Table $foreign) {
		parent::__construct($service);
		$this->model = $model;
		$this->foreign = $foreign;
	}

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$this->generateGetPayloadMethods($class, $this->twig->render('to-many/getPayloadMethods.twig'));
		$this->generateNotFound($class);

		// method: read(Request $request, PayloadInterface $payload) : Response
		$modelName = $this->modelService->getModelNameByAction($action);
		$model = $this->modelService->getModel($modelName);
		
		$read = $this->generatePayloadMethod('read', $this->twig->render('to-one/read.twig', [
			'class' => $model->getPhpName(),
			'related' => $this->foreign->getCamelCaseName()
		]));
		
		$class->setMethod($read);
		$class->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName());
		
		// method: notUpdated(Request $request, PayloadInterface $payload)
		$notUpdated = $this->generatePayloadMethod('notUpdated', $this->twig->render('payload/notUpdated.twig'));
		$class->setMethod($notUpdated);
	}
}