<?php
namespace keeko\tools\generator\action;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;

class ModelCreateActionGenerator extends AbstractModelActionGenerator {
	
	/* (non-PHPdoc)
	 * @see \keeko\tools\generator\AbstractTraitGenerator::addMethods()
	 */
	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$modelName = $this->modelService->getModelNameByAction($action);
		$model = $this->modelService->getModel($modelName);

		// method: run(Request $request)
		$class->addUseStatement('phootwork\\json\\Json');
		$class->addUseStatement(str_replace('model', 'domain', $model->getNamespace()) . '\\' . $model->getPhpName() . 'Domain');
		$class->setMethod($this->generateRunMethod($this->twig->render('create-run.twig', [
			'domain' => $model->getPhpName() . 'Domain'
		])));
	}
}