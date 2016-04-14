<?php
namespace keeko\tools\generator\action;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;

class SkeletonActionGenerator extends AbstractActionGenerator {

	/**
	 * Add default blank methods
	 * 
	 * @param PhpClass $class
	 */
	public function generate(ActionSchema $action) {
		$class = $this->generateClass($action);
		$class = $this->loadClass($class);
		
		$this->ensureBasicSetup($class);
		
		// method: configureParams(OptionsResolver $resolver)
// 		$this->addConfigureParamsMethod($class, '');

		// method: run(Request $request) : Response
		if (!$class->hasMethod('run')) {
			$class->setMethod($this->generateRunMethod($this->twig->render('skeleton-run.twig')));
		}

		return $class;
	}
}