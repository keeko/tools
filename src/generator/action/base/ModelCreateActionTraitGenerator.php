<?php
namespace keeko\tools\generator\action\base;

use gossi\codegen\model\PhpTrait;
use keeko\core\schema\ActionSchema;
use keeko\tools\generator\AbstractActionTraitGenerator;
use keeko\tools\utils\NameUtils;

class ModelCreateActionTraitGenerator extends AbstractActionTraitGenerator {
	
	/* (non-PHPdoc)
	 * @see \keeko\tools\generator\AbstractTraitGenerator::addMethods()
	 */
	protected function addMethods(PhpTrait $trait, ActionSchema $action) {
		$modelName = $this->modelService->getModelNameByAction($action);
		$modelVariableName = NameUtils::toCamelCase($modelName);
		$modelObjectName = NameUtils::toStudlyCase($modelName);
		$fullModelObjectName = $this->modelService->getFullModelObjectName($action);

		// method: run(Request $request)
		$trait->addUseStatement($fullModelObjectName);
		$trait->addUseStatement('keeko\\core\\exceptions\\ValidationException');
		$trait->addUseStatement('keeko\\core\\utils\\HydrateUtils');
		$trait->setMethod($this->generateRunMethod($this->twig->render('create-run.twig', [
			'model' => $modelVariableName,
			'class' => $modelObjectName,
			'fields' => $this->codegenService->getWriteFields($modelName)
		])));
	}
}