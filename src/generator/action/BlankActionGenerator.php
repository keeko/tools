<?php
namespace keeko\tools\generator\action;

use gossi\codegen\model\PhpClass;

class BlankActionGenerator extends AbstractActionGenerator {

	/**
	 * Add default blank methods
	 * 
	 * @param PhpClass $class
	 */
	public function generate(PhpClass $class) {
		// add use statements
		$this->ensureUseStatements($class);
		
		// method: configureParams(OptionsResolver $resolver)
// 		$this->addConfigureParamsMethod($class, '');

		// method: run(Request $request) : Response
		$class->setMethod($this->generateRunMethod($this->twig->render('blank-run.twig')));

		return $class;
	}
}