<?php
namespace keeko\tools\helpers;

use keeko\tools\services\CommandService;
use keeko\tools\services\IOService;
use keeko\tools\config\ToolsConfig;
use Symfony\Component\Console\Logger\ConsoleLogger;
use keeko\tools\model\Project;
use keeko\tools\services\PackageService;
use keeko\tools\services\ModelService;
use keeko\tools\services\JsonService;
use keeko\tools\services\CodeGeneratorService;
use keeko\tools\generator\GeneratorFactory;

trait ServiceLoaderTrait {
	
	/** @var CommandService */
	protected $service;
	
	/** @var IOService */
	protected $io;
	
	/** @var ToolsConfig */
	protected $config;
	
	/** @var ConsoleLogger */
	protected $logger;
	
	/** @var Project */
	protected $project;
	
	/** @var GeneratorFactory */
	protected $factory;
	
	/** @var PackageService */
	protected $packageService;
	
	/** @var ModelService */
	protected $modelService;
	
	/** @var JsonService */
	protected $jsonService;
	
	/** @var CodeGeneratorService */
	protected $codegenService;
	
	protected function loadServices(CommandService $service) {
		$this->service = $service;
		$this->factory = $service->getFactory();

		$this->io = $service->getIOService();
		$this->config = $service->getConfig();
		$this->logger = $service->getLogger();
		$this->project = $service->getProject();

		$this->packageService = $service->getPackageService();
		$this->modelService = $service->getModelService();
		$this->jsonService = $service->getJsonService();
		$this->codegenService = $service->getCodeGeneratorService();
	}
}