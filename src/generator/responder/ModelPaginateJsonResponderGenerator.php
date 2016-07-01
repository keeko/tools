<?php
namespace keeko\tools\generator\responder;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;

class ModelPaginateJsonResponderGenerator extends AbstractModelJsonResponderGenerator {

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$this->generateGetPayloadMethods($class, $this->twig->render('model/getPayloadMethods-paginate.twig'));

		// method: found(Request $request, Found $payload) : JsonResponse
		$modelName = $this->modelService->getModelNameByAction($action);
		$model = $this->modelService->getModel($modelName);
		$fields = $this->getModelFields($model);
		foreach ($fields as $field) {
			$class->addUseStatement($field->getNamespace() . '\\' . $field->getPhpName());
		}

		$includes = $this->codeService->arrayToCode($this->getRelationshipIncludes($model));
		$found = $this->generatePayloadMethod('found', $this->twig->render('model/paginate.twig', [
			'class' => $model->getPhpName(),
			'includes' => $includes,
			'fields' => $this->getFieldsCode($fields)
		]), 'Found');

		$class->setMethod($found);
		$class->addUseStatement('keeko\\framework\\domain\\payload\\Found');
		$class->addUseStatement('Tobscure\\JsonApi\\Document');
		$class->addUseStatement('Tobscure\\JsonApi\\Collection');
		$class->addUseStatement('Tobscure\\JsonApi\\Parameters');
		$class->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName());
	}

}