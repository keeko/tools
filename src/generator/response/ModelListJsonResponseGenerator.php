<?php
namespace keeko\tools\generator\response;

use gossi\codegen\model\PhpClass;
use keeko\core\schema\ActionSchema;
use keeko\tools\generator\AbstractJsonResponseGenerator;

class ModelListJsonResponseGenerator extends AbstractJsonResponseGenerator {

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$modelName = $this->modelService->getModelNameByAction($action);
		$model = $this->modelService->getModel($modelName);

		// method: run(Request $request, $data = null)
		$class->addUseStatement('Tobscure\\JsonApi\\Document');
		$class->addUseStatement('Tobscure\\JsonApi\\Collection');
		$class->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName());
		$class->setMethod($this->generateRunMethod($this->twig->render('list-run.twig', [
			'class' => $model->getPhpName()
		])));
	}
}