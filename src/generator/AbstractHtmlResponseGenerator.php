<?php
namespace keeko\tools\generator;

use keeko\tools\generator\AbstractResponseGenerator;
use keeko\core\schema\ActionSchema;

class AbstractHtmlResponseGenerator extends AbstractResponseGenerator {
	
	protected function getTemplateFolder() {
		return 'response/json';
	}
	
	/**
	 * Generates a html response class for the given action
	 *
	 * @param ActionSchema $action
	 * @return PhpClass
	 */
	public function generate(ActionSchema $action) {
		return $this->doGenerate($action, 'json');
	}
}