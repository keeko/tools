<?php
namespace keeko\tools\generator;

use keeko\tools\helpers\ServiceLoaderTrait;
use keeko\tools\services\CommandService;

abstract class AbstractCodeGenerator {
	
	use ServiceLoaderTrait;

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
	 * @return CommandService
	 */
	protected function getService() {
		return $this->service;
	}

}
