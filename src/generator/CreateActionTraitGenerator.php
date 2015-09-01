<?php
namespace keeko\tools\generator;

use keeko\core\schema\ActionSchema;
use gossi\codegen\model\PhpTrait;

class CreateActionTraitGenerator extends AbstractTraitGenerator {
	
	/* (non-PHPdoc)
	 * @see \keeko\tools\generator\AbstractTraitGenerator::addMethods()
	 */
	protected function addMethods(PhpTrait $trait, ActionSchema $action) {
		$modelName = $this->getModelNameByAction($action);
		$model = $this->getModel($modelName);
		$fullModelObjectName = $this->getFullModelObjectName($action);

		// method: body()
		$trait->addUseStatement($fullModelObjectName);
		$trait->addUseStatement('keeko\\core\\exceptions\\ValidationException');
		$trait->addUseStatement('keeko\\core\\utils\\HydrateUtils');
		$trait->setMethod($this->generateRunMethod($this->twig->render('create-run.twig', [
			'model' => $model,
			'class' => $modelName,
			'fields' => $this->getWriteFields($modelName)
		])));
	}
}