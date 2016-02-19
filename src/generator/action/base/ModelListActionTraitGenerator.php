<?php
namespace keeko\tools\generator\action\base;

use gossi\codegen\model\PhpTrait;
use keeko\core\schema\ActionSchema;
use keeko\tools\generator\AbstractActionTraitGenerator;
use keeko\tools\utils\NameUtils;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;

class ModelListActionTraitGenerator extends AbstractActionTraitGenerator {
		
	/* (non-PHPdoc)
	 * @see \keeko\tools\generator\AbstractTraitGenerator::addMethods()
	 */
	protected function addMethods(PhpTrait $trait, ActionSchema $action) {
		$modelName = $this->modelService->getModelNameByAction($action);
		$modelVariableName = NameUtils::toCamelCase($modelName);
		$modelObjectName = NameUtils::toStudlyCase($modelName);
		$fullModelObjectName = $this->modelService->getFullModelObjectName($action);

		// method: configureParams(OptionsResolver $resolver)
		$this->addConfigureParamsMethod($trait, $this->twig->render('list-configureParams.twig'));

		// method: run(Request $request)
		$trait->addUseStatement('keeko\\core\\utils\\NameUtils');
		$trait->addUseStatement($fullModelObjectName . 'Query');
		$trait->setMethod($this->generateRunMethod($this->twig->render('list-run.twig', [
			'model' => $modelVariableName,
			'class' => $modelObjectName
		])));

		// method: applyFilter(*Query $query)
		$trait->setMethod(PhpMethod::create('applyFilter')
			->addParameter(PhpParameter::create('query')
				->setType($modelObjectName . 'Query')
			)
			->setBody('')
			->setDescription('Applies filtering on the query.')
			->setLongDescription('Overwrite this method on the action class to implement this functionality')
		);
			
	}

}
