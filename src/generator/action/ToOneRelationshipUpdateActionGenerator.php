<?php
namespace keeko\tools\generator\action;

use gossi\codegen\model\PhpClass;
use keeko\tools\model\Relationship;
use keeko\framework\schema\ActionSchema;

class ToOneRelationshipUpdateActionGenerator extends AbstractActionGenerator {

	/**
	 * Add default blank methods
	 * 
	 * @param ActionSchema $action
	 * @param Relationship $relationship
	 * @return PhpClass
	 */
	public function generate(ActionSchema $action, Relationship $relationship) {
		$model = $relationship->getModel();
		$class = $this->generateClass($action);
		
		// add use statements
		$this->ensureBasicSetup($class);
		
		// method: configureParams(OptionsResolver $resolver)
		$this->addConfigureParamsMethod($class, $this->twig->render('relationship-configureParams.twig'));

		// method: run(Request $request) : Response
		$name = $relationship->getRelatedName();
		$class->addUseStatement('phootwork\\json\\Json');
		$class->addUseStatement('Tobscure\\JsonApi\\Exception\\InvalidParameterException');
		$class->addUseStatement(str_replace('model', 'domain', $model->getNamespace()) . '\\' . $model->getPhpName() . 'Domain');
		$class->setMethod($this->generateRunMethod($this->twig->render('to-one-update-run.twig', [
			'domain' =>  $model->getPhpName() . 'Domain',
			'foreign' => $name
		])));

		return $class;
	}
}