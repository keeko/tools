<?php
namespace keeko\tools\generator;

use gossi\codegen\model\AbstractPhpStruct;
use keeko\framework\schema\ActionSchema;

class AbstractJsonResponseGenerator extends AbstractResponseGenerator {

	protected function getTemplateFolder() {
		return 'response/json';
	}
	
	protected function ensureUseStatements(AbstractPhpStruct $struct) {
		parent::ensureUseStatements($struct);
		$struct->removeUseStatement('Symfony\\Component\\HttpFoundation\\Response');
		$struct->addUseStatement('Symfony\\Component\\HttpFoundation\\JsonResponse');
	}
	
	protected function generateRunMethod($body = '') {
		$method = parent::generateRunMethod($body);
		$method->setType('JsonResponse');
		return $method;
	}

	/**
	 * Generates a json response class for the given action
	 *
	 * @param ActionSchema $action
	 * @return PhpClass
	 */
	public function generate(ActionSchema $action) {
		return $this->doGenerate($action, 'json');
	}
}
