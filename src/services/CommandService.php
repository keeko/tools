<?php
namespace keeko\tools\services;

use Symfony\Component\Console\Command\Command;
use keeko\tools\model\Project;
use keeko\tools\config\ToolsConfig;

class CommandService {

	private $io;
	private $command;
	private $config;
	private $packageService;
	private $modelService;
	private $jsonService;
	private $codegenService;
	private $project;

	public function __construct(Command $command) {
		$this->io = new IOService($command);
		$this->command = $command;
		$this->config = new ToolsConfig();

		$input = $this->io->getInput();
		$workdir = $input->hasOption('workdir') ? $input->getOption('workdir') : getcwd();
		$this->project = new Project($workdir);
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
	 * @return PackageService
	 */
	public function getPackageService() {
		if ($this->packageService === null) {
			$this->packageService = new PackageService($this);
		}
		
		return $this->packageService;
	}

	/**
	 * @return ModelService
	 */
	public function getModelService() {
		if ($this->modelService === null) {
			$this->modelService = new ModelService($this);
		}
		
		return $this->modelService;
	}

	/**
	 * @return JsonService
	 */
	public function getJsonService() {
		if ($this->jsonService === null) {
			$this->jsonService = new JsonService($this);
		}
		
		return $this->jsonService;
	}
	
	/**
	 * @return CodeGeneratorService
	 */
	public function getCodeGeneratorService() {
		if ($this->codegenService === null) {
			$this->codegenService = new CodeGeneratorService($this);
		}
		
		return $this->codegenService;
	}
}
