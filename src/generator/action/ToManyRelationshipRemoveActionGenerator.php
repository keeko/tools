<?php
namespace keeko\tools\generator\action;

use gossi\codegen\model\PhpClass;
use Propel\Generator\Model\Table;

class ToManyRelationshipRemoveActionGenerator extends AbstractActionGenerator {

	/**
	 * Add default blank methods
	 * 
	 * @param PhpClass $class
	 */
	public function generate(PhpClass $class, Table $model, Table $foreign) {
		// add use statements
		$this->ensureBasicSetup($class);
		
		// method: configureParams(OptionsResolver $resolver)
		$this->addConfigureParamsMethod($class, $this->twig->render('relationship-configureParams.twig'));

		// method: run(Request $request) : Response
		$class->addUseStatement('phootwork\\json\\Json');
		$class->addUseStatement('Tobscure\\JsonApi\\Exception\\InvalidParameterException');
		$class->addUseStatement(str_replace('model', 'domain', $model->getNamespace()) . '\\' . $model->getPhpName() . 'Domain');
		$class->setMethod($this->generateRunMethod($this->twig->render('to-many-remove-run.twig', [
			'domain' =>  $model->getPhpName() . 'Domain',
			'foreign_class' => $foreign->getPhpName()
		])));

		return $class;
	}
}