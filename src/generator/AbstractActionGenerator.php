<?php
namespace keeko\tools\generator;

use keeko\tools\generator\AbstractCodeGenerator;
use gossi\codegen\model\AbstractPhpStruct;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;

class AbstractActionGenerator extends AbstractCodeGenerator {

	protected function getTemplateFolder() {
		return 'actions';
	}
	
	protected function generateRunMethod($body = '') {
		return PhpMethod::create('run')
			->setDescription('Automatically generated run method')
			->setType('Response')
			->addParameter(PhpParameter::create('request')->setType('Request'))
			->setBody($body);
	}

	protected function addConfigureParamsMethod(AbstractPhpStruct $struct, $body = '') {
		$struct->addUseStatement('Symfony\\Component\\OptionsResolver\\OptionsResolver');
		$struct->setMethod(PhpMethod::create('configureParams')
			->addParameter(PhpParameter::create('resolver')->setType('OptionsResolver'))
			->setBody($body)
		);
	}
	
	protected function ensureUseStatements(AbstractPhpStruct $struct) {
		$struct->addUseStatement('keeko\\core\\package\\AbstractAction');
		$struct->addUseStatement('Symfony\\Component\\HttpFoundation\\Request');
		$struct->addUseStatement('Symfony\\Component\\HttpFoundation\\Response');
	}

}
