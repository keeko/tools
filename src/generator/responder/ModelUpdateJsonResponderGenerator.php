<?php
namespace keeko\tools\generator\responder;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;

class ModelUpdateJsonResponderGenerator extends AbstractPayloadJsonResponderGenerator {

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$this->generateGetPayloadMethods($class, $this->twig->render('model/getPayloadMethods-update.twig'));
		$this->generateNotFound($class);
		$this->generateNotValid($class);
		
		// method: updated(Request $request, Updated $payload) : JsonResponse
		$modelName = $this->modelService->getModelNameByAction($action);
		$model = $this->modelService->getModel($modelName);

		$fields = $this->getModelFields($model);
		foreach ($fields as $field) {
			$class->addUseStatement($field->getNamespace() . '\\' . $field->getPhpName());
		}
		
		$updated = $this->generatePayloadMethod('updated', $this->twig->render('payload/read.twig', [
			'class' => $model->getPhpName(),
			'includes' => $this->codegenService->arrayToCode($this->getRelationshipIncludes($model)),
			'fields' => $this->getFieldsCode($fields)
		]), 'Updated');
		
		$class->setMethod($updated);
		$class->addUseStatement('keeko\\framework\\domain\\payload\\Updated');
		$class->addUseStatement('Tobscure\\JsonApi\\Document');
		$class->addUseStatement('Tobscure\\JsonApi\\Resource');
		$class->addUseStatement('Tobscure\\JsonApi\\Parameters');
		$class->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName());
		
		// method: notUpdated(Request $request, NotUpdated $payload) : JsonResponse
		$notUpdated = $this->generatePayloadMethod('notUpdated', $this->twig->render('payload/notUpdated.twig'),
			'NotUpdated');
		$class->setMethod($notUpdated);
		$class->addUseStatement('keeko\\framework\\domain\\payload\\NotUpdated');
	}
}