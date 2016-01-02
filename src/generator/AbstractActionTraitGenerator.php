<?php
namespace keeko\tools\generator;

use gossi\codegen\model\PhpTrait;
use keeko\core\schema\ActionSchema;
use keeko\tools\generator\AbstractActionGenerator;
use gossi\codegen\model\AbstractPhpStruct;

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
		$struct->removeUseStatement('keeko\\core\\package\\AbstractAction');
		$struct->addUseStatement('Symfony\\Component\\HttpFoundation\\Request');
		$struct->addUseStatement('Symfony\\Component\\HttpFoundation\\Response');
	}
}