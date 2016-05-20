<?php
namespace keeko\tools\generator\action;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;

class ModelListActionGenerator extends AbstractModelActionGenerator {
		
	/* (non-PHPdoc)
	 * @see \keeko\tools\generator\AbstractTraitGenerator::addMethods()
	 */
	protected function addMethods(PhpClass $class, ActionSchema $action) {
		$modelName = $this->modelService->getModelNameByAction($action);
		$model = $this->modelService->getModel($modelName);
		
		// method: configureParams(OptionsResolver $resolver)
		$this->addConfigureParamsMethod($class, $this->twig->render('list-configureParams.twig'));

		// method: run(Request $request)
		$class->addUseStatement('keeko\\framework\\utils\\Parameters');
		$class->addUseStatement(str_replace('model', 'domain', $model->getNamespace()) . '\\' . $model->getPhpName() . 'Domain');
		$class->setMethod($this->generateRunMethod($this->twig->render('list-run.twig', [
			'domain' => $model->getPhpName() . 'Domain'
		])));
			
	}

}
