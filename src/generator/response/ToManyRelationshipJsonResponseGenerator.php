<?php
namespace keeko\tools\generator\response;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;
use keeko\framework\utils\NameUtils;
use Propel\Generator\Model\Table;

class ToManyRelationshipJsonResponseGenerator extends AbstractJsonResponseGenerator {
	
	/** @var Table */
	private $foreign;
	
	/** @var Table */
	private $model;
	
	public function __construct($service, Table $model, Table $foreign) {
		parent::__construct($service);
		$this->model = $model;
		$this->foreign = $foreign;
	}

	protected function addMethods(PhpClass $class, ActionSchema $action) {
		// method: run(Request $request, $data = null)
		$class->addUseStatement($this->model->getNamespace() . '\\' . $this->model->getPhpName());
		$class->setMethod($this->generateRunMethod($this->twig->render('dump-to-many-relationship.twig', [
			'class' => $this->model->getPhpName(),
			'related' => NameUtils::pluralize($this->foreign->getCamelCaseName())
		])));
	}
}