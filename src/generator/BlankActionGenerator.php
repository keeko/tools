<?php
namespace keeko\tools\generator;

use keeko\tools\generator\AbstractActionGenerator;
use gossi\codegen\model\PhpClass;

class BlankActionGenerator extends AbstractActionGenerator {

	/**
	 * Add default blank methods
	 * 
	 * @param PhpClass $class
	 */
	public function generate(PhpClass $class) {
		// add use statements
		$this->addUseStatements($class);
		
		// method: setDefaultParams(OptionsResolverInterface $resolver)
// 		$this->addSetDefaultParamsMethod($class, '');

		// method: run(Request $request)
		$class->setMethod($this->generateRunMethod($this->twig->render('blank-run.twig')));

		return $class;
	}
}