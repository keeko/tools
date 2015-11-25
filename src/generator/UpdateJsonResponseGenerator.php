<?php
namespace keeko\tools\generator;

use keeko\core\schema\ActionSchema;
use keeko\tools\utils\NameUtils;
use gossi\codegen\model\PhpClass;

class UpdateJsonResponseGenerator extends AbstractJsonResponseGenerator {

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$modelName = $this->modelService->getModelNameByAction($action);
		$modelVariableName = NameUtils::toCamelCase($modelName);

		// method: run(Request $request, $data = null)
		$class->setMethod($this->generateRunMethod($this->twig->render('dump-model.twig', [
			'model' => $modelVariableName
		])));
	}
}