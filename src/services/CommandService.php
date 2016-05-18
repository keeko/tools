<?php
namespace keeko\tools\services;

use Symfony\Component\Console\Command\Command;
use keeko\tools\model\Project;
use keeko\tools\config\ToolsConfig;
use Symfony\Component\Console\Logger\ConsoleLogger;
use keeko\tools\generator\GeneratorFactory;
use keeko\tools\command\AbstractKeekoCommand;

class CommandService {

	private $io;
	private $command;
	private $config;
	private $logger;
	private $project;
	
	private $packageService;
	private $modelService;
	private $jsonService;
	private $codegenService;
	
	private $factory;
	
	public function __construct(Command $command, ConsoleLogger $logger) {
		$this->io = new IOService($command);
		$this->command = $command;
		$this->logger = $logger;
		$this->config = new ToolsConfig();
		$this->factory = new GeneratorFactory($this);

		$input = $this->io->getInput();
		$wd = $input->getOption('workdir');
		$workdir = $wd !== null ? $wd : getcwd();
		$this->project = new Project($workdir);
		
		$this->packageService = new PackageService();
		$this->modelService = new ModelService();
		$this->jsonService = new JsonService();
		$this->codegenService = new CodeGeneratorService();
		
		$this->packageService->setService($this);
		$this->modelService->setService($this);
		$this->jsonService->setService($this);
		$this->codegenService->setService($this);
		
	}
	
	/**
	 * @return AbstractKeekoCommand
	 */
	public function getCommand() {
		return $this->command;
	}

	/**
	 * @return ToolsConfig
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * @return IOService
	 */
	public function getIOService() {
		return $this->io;
	}
	
	/**
	 * @return Project
	 */
	public function getProject() {
		return $this->project;
	}
	
	/**
	 * @return ConsoleLogger
	 */
	public function getLogger() {
		return $this->logger;
	}
	
	/**
	 * @return GeneratorFactory
	 */
	public function getFactory() {
		return $this->factory;
	}

	/**
	 * @return PackageService
	 */
	public function getPackageService() {
		return $this->packageService;
	}

	/**
	 * @return ModelService
	 */
	public function getModelService() {
		return $this->modelService;
	}

	/**
	 * @return JsonService
	 */
	public function getJsonService() {
		return $this->jsonService;
	}
	
	/**
	 * @return CodeGeneratorService
	 */
	public function getCodeGeneratorService() {
		return $this->codegenService;
	}
}
