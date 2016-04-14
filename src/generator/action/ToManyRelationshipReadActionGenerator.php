<?php
namespace keeko\tools\generator\action;

use gossi\codegen\model\PhpClass;
use Propel\Generator\Model\Table;

class ToManyRelationshipReadActionGenerator extends AbstractActionGenerator {

	/**
	 * Add default blank methods
	 * 
	 * @param PhpClass $class
	 */
	public function generate(PhpClass $class, Table $model, Table $foreign, Table $middle) {
		// add use statements
		$this->ensureBasicSetup($class);
		
		// method: configureParams(OptionsResolver $resolver)
		$this->addConfigureParamsMethod($class, $this->twig->render('relationship-configureParams.twig'));

		// method: run(Request $request) : Response
		$class->addUseStatement(str_replace('model', 'domain', $model->getNamespace()) . '\\' . $model->getPhpName() . 'Domain');
		$class->setMethod($this->generateRunMethod($this->twig->render('to-many-read-run.twig', [
			'domain' =>  $model->getPhpName() . 'Domain'
		])));

		return $class;
	}
}