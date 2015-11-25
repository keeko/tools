<?php
namespace keeko\tools\generator;

use gossi\codegen\model\AbstractPhpStruct;
use keeko\core\schema\ActionSchema;

class AbstractJsonResponseGenerator extends AbstractResponseGenerator {

	protected function getTemplateFolder() {
		return 'response/json';
	}
	
	protected function addUseStatements(AbstractPhpStruct $struct) {
		parent::addUseStatements($struct);
		$struct->addUseStatement('Symfony\\Component\\HttpFoundation\\JsonResponse');
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
