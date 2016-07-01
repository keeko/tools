<?php
namespace keeko\tools\services;

use Symfony\Component\Console\Command\Command;
use keeko\tools\model\Project;
use keeko\tools\config\ToolsConfig;
use Symfony\Component\Console\Logger\ConsoleLogger;
use keeko\tools\generator\GeneratorFactory;
use keeko\tools\command\AbstractKeekoCommand;

class CommandService {

	/** @var IOService */
	private $io;

	/** @var Command */
	private $command;

	/** @var ToolsConfig */
	private $config;

	/** @var ConsoleLogger */
	private $logger;

	/** @var Project */
	private $project;

	/** @var GeneratorFactory */
	private $factory;

	/** @var PackageService */
	private $packageService;

	/** @var ModelService */
	private $modelService;

	/** @var JsonService */
	private $jsonService;

	/** @var CodeService */
	private $codeService;

	/** @var GeneratorDefinitionService */
	private $generatorDefinitionService;

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
		$this->codeService = new CodeService();
		$this->generatorDefinitionService = new GeneratorDefinitionService();

		$this->packageService->setService($this);
		$this->modelService->setService($this);
		$this->jsonService->setService($this);
		$this->codeService->setService($this);
		$this->generatorDefinitionService->setService($this);

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
	 * @return CodeService
	 */
	public function getCodeService() {
		return $this->codeService;
	}

	/**
	 * @return GeneratorDefinitionService
	 */
	public function getGeneratorDefinitionService() {
		return $this->generatorDefinitionService;
	}
}
