<?php
namespace keeko\tools\generator\response;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;
use keeko\tools\generator\AbstractModelJsonResponseGenerator;

class ModelReadJsonResponseGenerator extends AbstractModelJsonResponseGenerator {

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$modelName = $this->modelService->getModelNameByAction($action);
		$model = $this->modelService->getModel($modelName);

		// method: run(Request $request, $data = null)
		$fields = $this->getModelFields($model);
		foreach ($fields as $field) {
			$class->addUseStatement($field->getNamespace() . '\\' . $field->getPhpName());
		}
		$class->addUseStatement('Tobscure\\JsonApi\\Document');
		$class->addUseStatement('Tobscure\\JsonApi\\Resource');
		$class->addUseStatement('Tobscure\\JsonApi\\Parameters');
		$class->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName());
		$class->setMethod($this->generateRunMethod($this->twig->render('dump-model.twig', [
			'class' => $model->getPhpName(),
			'includes' => $this->codegenService->arrayToCode($this->getRelationshipIncludes($model)),
			'fields' => $this->getFieldsCode($fields)
		])));
	}
}