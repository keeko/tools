<?php
namespace keeko\tools\generator;

use gossi\codegen\model\PhpTrait;
use keeko\core\schema\ActionSchema;
use keeko\tools\services\CommandService;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use keeko\tools\helpers\ServiceLoaderTrait;

abstract class AbstractCodeGenerator {
	
	use ServiceLoaderTrait;

	private $trait;
	
	/** @var \Twig_Environment */
	protected $twig;

	public function __construct(CommandService $service) {
		$this->loadServices($service);
		
		$loader = new \Twig_Loader_Filesystem($this->service->getConfig()->getTemplateRoot() . '/' . $this->getTemplateFolder());
		$this->twig = new \Twig_Environment($loader);
	}
	
	protected function getTemplateFolder() {
		return '';
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
			->setDescription('Base methods for ' . $action->getClass())
			->setLongDescription('This code is automatically created. Modifications will probably be overwritten.');
		
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