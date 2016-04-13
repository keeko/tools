<?php
namespace keeko\tools\generator\responder;

use keeko\framework\schema\ActionSchema;

class AbstractHtmlResponderGenerator extends AbstractResponderGenerator {
	
	protected function getTemplateFolder() {
		return 'responder/html';
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