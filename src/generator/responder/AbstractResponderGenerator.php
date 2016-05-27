<?php
namespace keeko\tools\generator\responder;

use gossi\codegen\model\AbstractPhpStruct;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use keeko\framework\schema\ActionSchema;
use keeko\tools\generator\AbstractCodeGenerator;

abstract class AbstractResponderGenerator extends AbstractCodeGenerator {
	
	protected function getTemplateFolder() {
		return 'responder';
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
	 * @return AbstractPhpStruct
	 */
	protected function doGenerate(ActionSchema $action, $format) {
		$struct = $this->generateStruct($action, $format);
	
		$this->codegenService->addAuthors($struct, $this->packageService->getPackage());
	
		$this->ensureUseStatements($struct);
		$this->addMethods($struct, $action);
	
		return $struct;
	}
	
	/**
	 * Generates the struct
	 *
	 * @param ActionSchema $action
	 * @param string $format
	 * @return AbstractPhpStruct
	 */
	protected function generateStruct(ActionSchema $action, $format) {
		return PhpClass::create($action->getResponder($format))
			->setParentClassName('AbstractResponder')
			->setDescription('Automatically generated ' . ucwords($format) . 'Responder for ' . $action->getTitle())
			->setLongDescription($action->getDescription());
	}
	
	protected function generateRunMethod($body = '') {
		return PhpMethod::create('run')
			->setDescription('Automatically generated run method')
			->setType('Response')
			->addParameter(PhpParameter::create('request')->setType('Request'))
			->addParameter(PhpParameter::create('payload')->setType('PayloadInterface')->setDefaultValue(null))
			->setBody($body);
	}

	protected function ensureUseStatements(AbstractPhpStruct $struct) {
		$struct->addUseStatement('keeko\\framework\\domain\\payload\\PayloadInterface');
		$struct->addUseStatement('keeko\\framework\\foundation\\AbstractResponder');
		$struct->addUseStatement('Symfony\\Component\\HttpFoundation\\Request');
		$struct->addUseStatement('Symfony\\Component\\HttpFoundation\\Response');
	}

}