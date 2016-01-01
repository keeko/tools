<?php
namespace keeko\tools\generator\response;

use gossi\codegen\model\PhpClass;
use keeko\core\schema\ActionSchema;
use keeko\tools\generator\AbstractJsonResponseGenerator;
use keeko\tools\utils\NameUtils;

class ReadJsonResponseGenerator extends AbstractJsonResponseGenerator {

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$modelName = $this->modelService->getModelNameByAction($action);
		$modelVariableName = NameUtils::toCamelCase($modelName);

		// method: run(Request $request, $data = null)
		$class->setMethod($this->generateRunMethod($this->twig->render('dump-model.twig', [
			'model' => $modelVariableName
		])));
	}
}