<?php
namespace keeko\tools\generator;

use gossi\codegen\model\PhpTrait;
use keeko\core\schema\ActionSchema;
use keeko\tools\services\CommandService;
use keeko\tools\helpers\IOServiceTrait;
use keeko\tools\helpers\ModelServiceTrait;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use keeko\tools\helpers\CodeGeneratorServiceTrait;

abstract class AbstractTraitGenerator {
	
	use IOServiceTrait;
	use ModelServiceTrait;
	use CodeGeneratorServiceTrait;

	private $trait;

	/** @var CommandService */
	protected $service;
	
	/** @var \Twig_Environment */
	protected $twig;

	public function __construct(CommandService $service) {
		$this->service = $service;
		
		$loader = new \Twig_Loader_Filesystem($this->service->getConfig()->getTemplateRoot() . '/actions');
		$this->twig = new \Twig_Environment($loader);
	}

	/**
	 * 
	 * @return CommandService
	 */
	protected function getService() {
		return $this->service;
	}

	/**
	 * @param string $name 
	 * @param ActionSchema $action
	 * @return PhpTrait
	 */
	public function generate($name, ActionSchema $action) {
		$this->trait = PhpTrait::create($name)
			->addUseStatement('Symfony\\Component\\HttpFoundation\\Request')
			->addUseStatement('Symfony\\Component\\HttpFoundation\\Response')
			->setDescription('Base methods for ' . $action->getTitle())
			->setLongDescription('This code is automatically created. Modifications will probably be overwritten');
		
		$this->addMethods($this->trait, $action);
		
		return $this->trait;
	}
	
	protected function addMethods(PhpTrait $trait, ActionSchema $action) {
	}

	protected function generateRunMethod($body = '') {
		return PhpMethod::create('run')
			->setDescription('Automatically generated run method')
			->setType('Response')
			->addParameter(PhpParameter::create('request')->setType('Request'))
			->setBody($body);
	}

	protected function addSetDefaultParamsMethod(PhpTrait $trait, $body = '') {
		$trait->addUseStatement('Symfony\\Component\\OptionsResolver\\OptionsResolverInterface');
		$trait->setMethod(PhpMethod::create('setDefaultParams')
			->addParameter(PhpParameter::create('resolver')->setType('OptionsResolverInterface'))
			->setBody($body)
		);
	}
}