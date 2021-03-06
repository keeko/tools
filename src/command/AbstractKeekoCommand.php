<?php
namespace keeko\tools\command;

use keeko\framework\schema\PackageSchema;
use keeko\tools\helpers\IOHelper;
use keeko\tools\helpers\ServiceLoaderTrait;
use keeko\tools\services\CommandService;
use phootwork\lang\Text;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Application;

abstract class AbstractKeekoCommand extends Command {

	use ServiceLoaderTrait;
	
	/** @var PackageSchema */
	protected $package;

	public function __construct($name = null) {
		parent::__construct($name);
	}

	/* (non-PHPdoc)
	 * @see \Symfony\Component\Console\Command\Command::initialize()
	 */
	protected function initialize(InputInterface $input, OutputInterface $output) {
		// io
		$io = new IOHelper();
		$io->setInput($input);
		$io->setOutput($output);
		$this->getHelperSet()->set($io);
		
		// logger
		$logger = new ConsoleLogger($output, [
			LogLevel::NOTICE => OutputInterface::VERBOSITY_VERBOSE,
			LogLevel::INFO   => OutputInterface::VERBOSITY_VERBOSE,
		]);
	
		// services
		$service = new CommandService($this, $logger);
		$this->loadServices($service);
		$this->package = $service->getPackageService()->getPackage();
	}

	/**
	 * @return CommandService
	 */
	public function getService() {
		// this is BULLSHIT!!
		// this should never be public
		// this is only because of the UI classes, some need access to the model service
		// see https://github.com/keeko/tools/issues/5 how this could be made more useful
		return $this->service;
	}


	protected function configure() {
		$this->configureGlobalOptions();
	}

	protected function configureGenerateOptions() {
		$this
			->addOption(
				'schema',
				's',
				InputOption::VALUE_OPTIONAL,
				'Path to the database schema (if ommited, res/database/schema.xml is used)',
				null
			)
			->addOption(
				'force',
				'f',
				InputOption::VALUE_NONE,
				'Forces to owerwrite'
			)
		;
	}
	
	protected function configureGlobalOptions() {
		$this
			->addOption(
				'workdir',
				'w',
				InputOption::VALUE_OPTIONAL,
				'Specify the working directory (if ommited, current working directory is used)',
				null
			)
		;
	}
	
	protected function runCommand($name, array $input = [], Application $app = null) {
		// return whether command has already executed
		if ($app === null) {
			$app = $this->getApplication();
		}
		$cmd = $app->find($name);

		$input = new ArrayInput($this->sanitizeInput($input));
		$input->setInteractive(false);
		
		$cmd->run($input, $this->io->getOutput());
	}
	
	/**
	 * Prepares input as used for running a command
	 * 
	 * @param array $input
	 * @return array
	 */
	private function sanitizeInput(array $input) {
		// add verbose level
		if (!isset($input['--verbose'])) {
			$input['--verbose'] = $this->io->getInput()->getOption('verbose');
		}

		// check if at least one argument is present and if not add a blank one
		$hasArgs = false;
		foreach (array_keys($input) as $key) {
			if (!Text::create($key)->startsWith('--')) {
				$hasArgs = true;
			}
		}
		if (!$hasArgs) {
			$input[] = '';
		}
	
		return $input;
	}

}