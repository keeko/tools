<?php
namespace keeko\tools\generator\action;

use gossi\codegen\model\PhpClass;
use keeko\tools\generator\AbstractActionGenerator;
use Propel\Generator\Model\Table;

class ToManyRelationshipAddActionGenerator extends AbstractActionGenerator {

	/**
	 * Add default blank methods
	 * 
	 * @param PhpClass $class
	 */
	public function generate(PhpClass $class, Table $model, Table $foreignModel, Table $middle) {
		// add use statements
		$this->ensureBasicSetup($class);
		
		// method: configureParams(OptionsResolver $resolver)
		$this->addConfigureParamsMethod($class, $this->twig->render('relationship-configureParams.twig'));

		// method: run(Request $request) : Response
		$class->addUseStatement('Symfony\\Component\\HttpFoundation\\Request');
		$class->addUseStatement('Symfony\\Component\\HttpFoundation\\Response');
		$class->addUseStatement('Symfony\\Component\\Routing\\Exception\\ResourceNotFoundException');
		$class->addUseStatement('Tobscure\\JsonApi\\Exception\\InvalidParameterException');
		$class->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName() . 'Query');
		$class->addUseStatement($model->getNamespace() . '\\' . $foreignModel->getPhpName() . 'Query');
		$class->setMethod($this->generateRunMethod($this->twig->render('to-many-add-run.twig', [
			'model' => $model->getCamelCaseName(),
			'class' => $model->getPhpName(),
			'foreign_model' => $foreignModel->getCamelCaseName(),
			'foreign_class' => $foreignModel->getPhpName()
		])));

		return $class;
	}
}