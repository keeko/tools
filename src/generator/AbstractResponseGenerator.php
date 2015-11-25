<?php
namespace keeko\tools\generator;

use gossi\codegen\model\AbstractPhpStruct;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use keeko\tools\generator\AbstractCodeGenerator;
use keeko\core\schema\ActionSchema;
use gossi\codegen\model\PhpClass;

abstract class AbstractResponseGenerator extends AbstractCodeGenerator {
	
	protected function getTemplateFolder() {
		return 'response';
	}
	
	/**
	 * Generates a response class for the given action
	 *
	 * @param ActionSchema $action
	 * @return PhpClass
	 */
	abstract public function generate(ActionSchema $action);
	
	/**
	 * Generates a response class for the given action
	 *
	 * @param ActionSchema $action
	 * @param string $format
	 * @return PhpClass
	 */
	protected function doGenerate(ActionSchema $action, $format) {
		$class = PhpClass::create($action->getResponse($format))
			->setParentClassName('AbstractResponse')
			->setDescription('Automatically generated ' . ucwords($format) . 'Response for ' . $action->getTitle())
			->setLongDescription($action->getDescription());
	
		$this->codegenService->addAuthors($class, $this->packageService->getPackage());
	
		$this->addUseStatements($class);
		$this->addMethods($class, $action);
	
		return $class;
	}
	
	
	
	protected function generateRunMethod($body = '') {
		return PhpMethod::create('run')
			->setDescription('Automatically generated run method')
			->setType('Response')
			->addParameter(PhpParameter::create('request')->setType('Request'))
			->addParameter(PhpParameter::create('data')->setDefaultValue('null'))
			->setBody($body);
	}

	protected function addUseStatements(AbstractPhpStruct $struct) {
		$struct->addUseStatement('keeko\\core\\action\\AbstractResponse');
		$struct->addUseStatement('Symfony\\Component\\HttpFoundation\\Request');
		$struct->addUseStatement('Symfony\\Component\\HttpFoundation\\Response');
	}

}