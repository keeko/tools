<?php
namespace keeko\tools\generator\action;

use gossi\codegen\model\AbstractPhpStruct;
use gossi\codegen\model\PhpTrait;
use keeko\framework\schema\ActionSchema;
use gossi\codegen\model\PhpClass;

class AbstractModelActionGenerator extends AbstractActionGenerator {
	
	/**
	 * Generates an action trait with the given name as classname
	 * 
	 * @param string $name
	 * @param ActionSchema $action
	 * @return PhpTrait
	 */
	public function generate(ActionSchema $action) {
		$class = PhpClass::create($action->getClass())
			->setDescription('Action Class for ' . $action->getName())
			->setLongDescription('This code is automatically created. Modifications will probably be overwritten.');
	
		$this->ensureBasicSetup($class);
		$this->addMethods($class, $action);

		return $class;
	}

	protected function addMethods(PhpClass $struct, ActionSchema $action) {
	}
	
	protected function ensureUseStatements(AbstractPhpStruct $struct) {
		parent::ensureUseStatements($struct);
		
	}
}