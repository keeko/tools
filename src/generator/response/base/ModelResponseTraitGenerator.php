<?php
namespace keeko\tools\generator\response\base;

use gossi\codegen\model\AbstractPhpStruct;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpTrait;
use keeko\core\schema\ActionSchema;
use keeko\tools\generator\AbstractResponseGenerator;
use keeko\tools\utils\NameUtils;

class ModelResponseTraitGenerator extends AbstractResponseGenerator {

	/**
	 * Generates a json response class for the given action
	 *
	 * @param ActionSchema $action
	 * @return PhpTrait
	 */
	public function generate(ActionSchema $action) {
		return $this->doGenerate($action, 'json');
	}
	
	/**
	 * Generates the struct
	 *
	 * @param ActionSchema $action
	 * @param string $format
	 * @return PhpTrat
	 */
	protected function generateStruct(ActionSchema $action, $format) {
		$modelName = $this->modelService->getModelNameByAction($action);
		$model = $this->modelService->getModel($modelName);

		$namespace = $this->modelService->getDatabase()->getNamespace();
		$nsModelName = $namespace . '\\' . $model->getPhpName();

		$response = $action->getResponse($format);
		$name = str_replace('action', 'response', $response);
		$ns = dirname(str_replace('\\', '/', $name));
		$name = str_replace('/', '\\', $ns) . '\\' . $model->getPhpName() . 'ResponseTrait';
		
		return PhpTrait::create($name)
			->setDescription('Automatically generated common response methods for ' . $modelName)
			->addUseStatement($nsModelName);
	}
	
	protected function ensureUseStatements(AbstractPhpStruct $struct) {
		$struct->addUseStatement('keeko\\core\\utils\\FilterUtils');
		$struct->addUseStatement('Propel\\Runtime\\Map\\TableMap');
	}
	
	protected function addMethods(PhpTrait $trait, ActionSchema $action) {
		$modelName = $this->modelService->getModelNameByAction($action);
		$model = $this->modelService->getModel($modelName);
		$modelVariableName = NameUtils::toCamelCase($modelName);
		$modelObjectName = $model->getPhpName();
		$codegen = $this->codegenService->getCodegen();

		// method: filter(array ${{model}})
		$trait->setMethod(PhpMethod::create('filter')
			->setDescription('Automatically generated method, will be overridden')
			->addParameter(PhpParameter::create($modelVariableName)->setType('array'))
			->setVisibility('protected')
			->setBody($this->twig->render('filter.twig', [
				'model' => $modelVariableName,
				'filter' => $this->codegenService->arrayToCode($codegen->getReadFilter($modelName))
			]))
		);
	
		// method: {{model}}toArray({{Model}} ${{model}})
		$trait->setMethod(PhpMethod::create($modelVariableName . 'ToArray')
			->setDescription('Automatically generated method, will be overridden')
			->addParameter(PhpParameter::create($modelVariableName)->setType($modelObjectName))
			->setVisibility('protected')
			->setBody($this->twig->render('modelToArray.twig', ['model' => $modelVariableName]))
		);
	}
	
}
