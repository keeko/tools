<?php
namespace keeko\tools\generator;

use gossi\codegen\model\PhpTrait;
use keeko\core\schema\ActionSchema;
use keeko\tools\utils\NameUtils;

class UpdateActionTraitGenerator extends AbstractActionTraitGenerator {

	/* (non-PHPdoc)
	 * @see \keeko\tools\generator\AbstractTraitGenerator::addMethods()
	 */
	protected function addMethods(PhpTrait $trait, ActionSchema $action) {
		$modelName = $this->modelService->getModelNameByAction($action);
		$modelVariableName = NameUtils::toCamelCase($modelName);
		$modelObjectName = NameUtils::toStudlyCase($modelName);
		$fullModelObjectName = $this->modelService->getFullModelObjectName($action);
	
		// method: setDefaultParams(OptionsResolverInterface $resolver)
		$this->addSetDefaultParamsMethod($trait, $this->twig->render('update-setDefaultParams.twig'));
	
		// method: run(Request $request)
		$trait->addUseStatement($fullModelObjectName);
		$trait->addUseStatement($fullModelObjectName . 'Query');
		$trait->addUseStatement('keeko\\core\\exceptions\\ValidationException');
		$trait->addUseStatement('keeko\\core\\utils\\HydrateUtils');
		$trait->addUseStatement('Symfony\\Component\\Routing\\Exception\\ResourceNotFoundException');
		$trait->setMethod($this->generateRunMethod($this->twig->render('update-run.twig', [
			'model' => $modelVariableName,
			'class' => $modelObjectName,
			'fields' => $this->codegenService->getWriteFields($modelName)
		])));
	}
}