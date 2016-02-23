<?php
namespace keeko\tools\generator\action\base;

use gossi\codegen\model\PhpTrait;
use keeko\framework\schema\ActionSchema;
use keeko\framework\utils\NameUtils;
use keeko\tools\generator\AbstractActionTraitGenerator;

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
		$trait->addUseStatement('phootwork\\json\\Json');
		$trait->addUseStatement($fullModelObjectName);
		$trait->addUseStatement('keeko\\framework\\exceptions\\ValidationException');
		$trait->setMethod($this->generateRunMethod($this->twig->render('create-run.twig', [
			'model' => $modelVariableName,
			'class' => $modelObjectName,
			'fields' => $this->codegenService->getWriteFields($modelName)
		])));
	}
}