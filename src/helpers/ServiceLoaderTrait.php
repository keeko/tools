<?php
namespace keeko\tools\helpers;

use keeko\tools\config\ToolsConfig;
use keeko\tools\generator\GeneratorFactory;
use keeko\tools\model\Project;
use keeko\tools\services\CodeService;
use keeko\tools\services\CommandService;
use keeko\tools\services\GeneratorDefinitionService;
use keeko\tools\services\IOService;
use keeko\tools\services\JsonService;
use keeko\tools\services\ModelService;
use keeko\tools\services\PackageService;
use Symfony\Component\Console\Logger\ConsoleLogger;

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

	/** @var CodeService */
	protected $codeService;

	/** @var GeneratorDefinitionService */
	protected $generatorDefinitionService;

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
		$this->codeService = $service->getCodeService();
		$this->generatorDefinitionService = $service->getGeneratorDefinitionService();
	}
}