<?php
namespace keeko\tools\generator\responder;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;

class ModelReadJsonResponderGenerator extends AbstractModelJsonResponderGenerator {

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$this->generateGetPayloadMethods($class, $this->twig->render('model/getPayloadMethods-read.twig'));
		$this->generateNotFound($class);

		// method: found(Request $request, Found $payload) : JsonResponse
		$modelName = $this->modelService->getModelNameByAction($action);
		$model = $this->modelService->getModel($modelName);

		$fields = $this->getModelFields($model);
		foreach ($fields as $field) {
			$class->addUseStatement($field->getNamespace() . '\\' . $field->getPhpName());
		}

		$found = $this->generatePayloadMethod('found', $this->twig->render('payload/read.twig', [
			'class' => $model->getPhpName(),
			'includes' => $this->codeService->arrayToCode($this->getRelationshipIncludes($model)),
			'fields' => $this->getFieldsCode($fields)
		]), 'Found');

		$class->setMethod($found);
		$class->addUseStatement('keeko\\framework\\domain\\payload\\Found');
		$class->addUseStatement('Tobscure\\JsonApi\\Document');
		$class->addUseStatement('Tobscure\\JsonApi\\Resource');
		$class->addUseStatement('Tobscure\\JsonApi\\Parameters');
		$class->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName());
	}
}