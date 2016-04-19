<?php
namespace keeko\tools\generator\action;

use gossi\codegen\model\PhpClass;
use keeko\tools\model\Relationship;
use keeko\framework\schema\ActionSchema;

class ToOneRelationshipReadActionGenerator extends AbstractActionGenerator {

	/**
	 * Add default blank methods
	 * 
	 * @param PhpClass $class
	 */
	public function generate(ActionSchema $action, Relationship $relationship) {
		$class = $this->generateClass($action);
		
		// add use statements
		$this->ensureBasicSetup($class);
		
		// method: configureParams(OptionsResolver $resolver)
		$this->addConfigureParamsMethod($class, $this->twig->render('relationship-configureParams.twig'));

		// method: run(Request $request) : Response
		$model = $relationship->getModel();
		$class->addUseStatement(str_replace('model', 'domain', $model->getNamespace()) . '\\' . $model->getPhpName() . 'Domain');
		$class->setMethod($this->generateRunMethod($this->twig->render('to-one-read-run.twig', [
			'domain' =>  $model->getPhpName() . 'Domain'
		])));

		return $class;
	}
}