<?php
namespace keeko\tools\generator\action;

use keeko\tools\generator\AbstractActionGenerator;
use gossi\codegen\model\PhpClass;

class NoopActionGenerator extends AbstractActionGenerator {

	/**
	 * Add default blank methods
	 * 
	 * @param PhpClass $class
	 */
	public function generate(PhpClass $class) {
		// add use statements
		$this->ensureUseStatements($class);

		return $class;
	}
}