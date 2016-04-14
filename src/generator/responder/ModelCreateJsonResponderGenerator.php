<?php
namespace keeko\tools\generator\responder;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;

class ModelCreateJsonResponderGenerator extends AbstractPayloadJsonResponderGenerator {

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$this->generateGetPayloadMethods($class, $this->twig->render('model/getPayloadMethods-create.twig'));
		$this->generateNotValid($class);
		
		// method: created(Request $request, PayloadInterface $payload)
		$modelName = $this->modelService->getModelNameByAction($action);
		$model = $this->modelService->getModel($modelName);

		$created = $this->generatePayloadMethod('created', $this->twig->render('model/created.twig', [
			'class' => $model->getPhpName()
		]));
		$class->setMethod($created);
		$class->addUseStatement('Tobscure\\JsonApi\\Document');
		$class->addUseStatement('Tobscure\\JsonApi\\Resource');
		$class->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName());
	}
}