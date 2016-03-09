<?php
namespace keeko\tools\generator\response;

use keeko\framework\schema\ActionSchema;
use keeko\tools\generator\AbstractResponseGenerator;

class AbstractHtmlResponseGenerator extends AbstractResponseGenerator {
	
	protected function getTemplateFolder() {
		return 'response/html';
	}
	
	/**
	 * Generates a html response class for the given action
	 *
	 * @param ActionSchema $action
	 * @return PhpClass
	 */
	public function generate(ActionSchema $action) {
		return $this->doGenerate($action, 'html');
	}
}