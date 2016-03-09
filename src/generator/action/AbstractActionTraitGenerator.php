<?php
namespace keeko\tools\generator\action;

use gossi\codegen\model\AbstractPhpStruct;
use gossi\codegen\model\PhpTrait;
use keeko\framework\schema\ActionSchema;

class AbstractActionTraitGenerator extends AbstractActionGenerator {
	
	/**
	 * Generates an action trait with the given name as classname
	 * 
	 * @param string $name
	 * @param ActionSchema $action
	 * @return PhpTrait
	 */
	public function generate($name, ActionSchema $action) {
		$trait = PhpTrait::create($name)
			->setDescription('Base methods for ' . $action->getClass())
			->setLongDescription('This code is automatically created. Modifications will probably be overwritten.');
	
		$this->ensureUseStatements($trait);
		$this->addMethods($trait, $action);

		return $trait;
	}

	protected function addMethods(PhpTrait $struct, ActionSchema $action) {
	}
	
	protected function ensureUseStatements(AbstractPhpStruct $struct) {
		parent::ensureUseStatements($struct);
		$struct->removeUseStatement('keeko\\framework\\foundation\\AbstractAction');
		$struct->addUseStatement('Symfony\\Component\\HttpFoundation\\Request');
		$struct->addUseStatement('Symfony\\Component\\HttpFoundation\\Response');
	}
}