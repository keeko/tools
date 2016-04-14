<?php
namespace keeko\tools\generator\responder;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;
use keeko\framework\utils\NameUtils;
use Propel\Generator\Model\Table;
use keeko\tools\services\CommandService;

class ToManyRelationshipJsonResponderGenerator extends AbstractPayloadJsonResponderGenerator {
	
	/** @var Table */
	private $foreign;
	
	/** @var Table */
	private $model;
	
	public function __construct(CommandService $service, Table $model, Table $foreign) {
		parent::__construct($service);
		$this->model = $model;
		$this->foreign = $foreign;
	}

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$this->generateGetPayloadMethods($class, $this->twig->render('to-many/getPayloadMethods.twig'));
		$this->generateNotValid($class);
		$this->generateNotFound($class);
		
		// method: updated(Request $request, PayloadInterface $payload) : Response
		$modelName = $this->modelService->getModelNameByAction($action);
		$model = $this->modelService->getModel($modelName);

		$updated = $this->generatePayloadMethod('updated', $this->twig->render('to-many/updated.twig', [
			'class' => $model->getPhpName(),
			'related' => NameUtils::pluralize($this->foreign->getCamelCaseName())
		]));
		
		$class->setMethod($updated);
		$class->addUseStatement($this->model->getNamespace() . '\\' . $this->model->getPhpName());
		
		// method: notUpdated(Request $request, PayloadInterface $payload)
		$notUpdated = $this->generatePayloadMethod('notUpdated', $this->twig->render('payload/notUpdated.twig'));
		$class->setMethod($notUpdated);
	}
}