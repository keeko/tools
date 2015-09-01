<?php
namespace keeko\tools\generator;

use gossi\codegen\model\PhpTrait;
use keeko\core\schema\ActionSchema;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;

class DeleteActionTraitGenerator extends AbstractTraitGenerator {

	/* (non-PHPdoc)
	 * @see \keeko\tools\generator\AbstractTraitGenerator::addMethods()
	 */
	protected function addMethods(PhpTrait $trait, ActionSchema $action) {
		$modelName = $this->getModelNameByAction($action);
		$model = $this->getModel($modelName);
		$fullModelObjectName = $this->getFullModelObjectName($action);

		// method: setDefaultParams(OptionsResolverInterface $resolver)
		$this->addSetDefaultParamsMethod($trait, $this->twig->render('delete-setDefaultParams.twig'));

		// method: body()
		$trait->addUseStatement($fullModelObjectName);
		$trait->addUseStatement($fullModelObjectName . 'Query');
		$trait->addUseStatement('Symfony\\Component\\Routing\\Exception\\ResourceNotFoundException');
		$trait->setMethod($this->generateRunMethod($this->twig->render('delete-run.twig', [
			'model' => $model,
			'class' => $modelName
		])));
	}

}
