<?php
namespace keeko\tools\generator\response;

use gossi\codegen\model\PhpClass;
use keeko\core\schema\ActionSchema;
use keeko\tools\generator\AbstractJsonResponseGenerator;

class ModelUpdateJsonResponseGenerator extends AbstractJsonResponseGenerator {

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$modelName = $this->modelService->getModelNameByAction($action);
		$model = $this->modelService->getModel($modelName);

		// method: run(Request $request, $data = null)
		$class->addUseStatement('Tobscure\\JsonApi\\Document');
		$class->addUseStatement('Tobscure\\JsonApi\\Resource');
		$class->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName());
		$class->setMethod($this->generateRunMethod($this->twig->render('dump-model.twig', [
			'class' => $model->getPhpName()
		])));
	}
}