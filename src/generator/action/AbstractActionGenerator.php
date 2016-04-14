<?php
namespace keeko\tools\generator\action;

use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use keeko\tools\generator\AbstractCodeGenerator;
use keeko\framework\schema\ActionSchema;

class AbstractActionGenerator extends AbstractCodeGenerator {

	protected function getTemplateFolder() {
		return 'action';
	}
	
	/**
	 * Generates the basic class
	 * 
	 * @param ActionSchema $action
	 * @return PhpClass
	 */
	protected function generateClass(ActionSchema $action) {
		$class = PhpClass::create($action->getClass());
		$class->setDescription($action->getTitle());
		$class->setLongDescription($action->getDescription() . "\n\n".
			'This code is automatically created. Modifications will probably be overwritten.');
		$this->codegenService->addAuthors($class);
		$this->ensureBasicSetup($class);
		
		return $class;
	}
	
	protected function generateRunMethod($body = '') {
		return PhpMethod::create('run')
			->setDescription('Automatically generated run method')
			->setType('Response')
			->addParameter(PhpParameter::create('request')->setType('Request'))
			->setBody($body);
	}

	protected function addConfigureParamsMethod(PhpClass $class, $body = '') {
		$class->addUseStatement('Symfony\\Component\\OptionsResolver\\OptionsResolver');
		$class->setMethod(PhpMethod::create('configureParams')
			->addParameter(PhpParameter::create('resolver')->setType('OptionsResolver'))
			->setBody($body)
		);
	}
	
	protected function ensureUseStatements(PhpClass $class) {
		$class->addUseStatement('keeko\\framework\\foundation\\AbstractAction');
		$class->addUseStatement('Symfony\\Component\\HttpFoundation\\Request');
		$class->addUseStatement('Symfony\\Component\\HttpFoundation\\Response');
	}
	
	protected function ensureBasicSetup(PhpClass $class) {
		$this->ensureUseStatements($class);
		$class->setParentClassName('AbstractAction');
	}

}
