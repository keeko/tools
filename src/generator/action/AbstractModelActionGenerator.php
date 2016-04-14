<?php
namespace keeko\tools\generator\action;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;

class AbstractModelActionGenerator extends AbstractActionGenerator {
	
	/**
	 * Generates an action trait with the given name as classname
	 * 
	 * @param ActionSchema $action
	 * @return PhpClass
	 */
	public function generate(ActionSchema $action) {
		$class = $this->generateClass($action);
		$this->addMethods($class, $action);

		return $class;
	}

	protected function addMethods(PhpClass $struct, ActionSchema $action) {
	}
}