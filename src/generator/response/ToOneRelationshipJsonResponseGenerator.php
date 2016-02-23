<?php
namespace keeko\tools\generator\response;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;
use keeko\tools\generator\AbstractJsonResponseGenerator;
use Propel\Generator\Model\Table;

class ToOneRelationshipJsonResponseGenerator extends AbstractJsonResponseGenerator {
	
	/** @var Table */
	private $model;
	
	/** @var Table */
	private $foreign;
	
	public function __construct($service, Table $model, Table $foreign) {
		parent::__construct($service);
		$this->model = $model;
		$this->foreign = $foreign;
	}

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		// method: run(Request $request, $data = null)
		$class->addUseStatement($this->model->getNamespace() . '\\' . $this->model->getPhpName());
		$class->setMethod($this->generateRunMethod($this->twig->render('dump-to-one-relationship.twig', [
			'class' => $this->model->getPhpName(),
			'related' => $this->foreign->getCamelCaseName()
		])));
	}
}