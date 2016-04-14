<?php
namespace keeko\tools\generator\action;

use gossi\codegen\model\PhpClass;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;

class ToOneRelationshipUpdateActionGenerator extends AbstractActionGenerator {

	/**
	 * Add default blank methods
	 * 
	 * @param PhpClass $class
	 */
	public function generate(PhpClass $class, Table $model, Table $foreign, ForeignKey $fk) {
		// add use statements
		$this->ensureBasicSetup($class);
		
		// method: configureParams(OptionsResolver $resolver)
		$this->addConfigureParamsMethod($class, $this->twig->render('relationship-configureParams.twig'));

		// method: run(Request $request) : Response
		$name = $fk->getPhpName();
		if (empty($name)) {
			$name = $foreign->getPhpName();
		}
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