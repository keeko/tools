<?php
namespace keeko\tools\generator\action\base;

use gossi\codegen\model\PhpTrait;
use keeko\framework\schema\ActionSchema;
use keeko\framework\utils\NameUtils;
use keeko\tools\generator\action\AbstractActionTraitGenerator;

class ModelUpdateActionTraitGenerator extends AbstractActionTraitGenerator {

	/* (non-PHPdoc)
	 * @see \keeko\tools\generator\AbstractTraitGenerator::addMethods()
	 */
	protected function addMethods(PhpTrait $trait, ActionSchema $action) {
		$modelName = $this->modelService->getModelNameByAction($action);
		$modelVariableName = NameUtils::toCamelCase($modelName);
		$modelObjectName = NameUtils::toStudlyCase($modelName);
		$fullModelObjectName = $this->modelService->getFullModelObjectName($action);
	
		// method: configureParams(OptionsResolver $resolver)
		$this->addConfigureParamsMethod($trait, $this->twig->render('update-configureParams.twig'));
	
		// method: run(Request $request)
		$trait->addUseStatement('phootwork\\json\\Json');
		$trait->addUseStatement($fullModelObjectName);
		$trait->addUseStatement($fullModelObjectName . 'Query');
		$trait->addUseStatement('keeko\\framework\\exceptions\\ValidationException');
		$trait->addUseStatement('Symfony\\Component\\Routing\\Exception\\ResourceNotFoundException');
		$trait->setMethod($this->generateRunMethod($this->twig->render('update-run.twig', [
			'model' => $modelVariableName,
			'class' => $modelObjectName
		])));
	}
}