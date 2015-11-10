<?php
namespace keeko\tools\command;

use keeko\core\schema\PackageSchema;
use keeko\tools\helpers\IOHelper;
use keeko\tools\helpers\ServiceLoaderTrait;
use keeko\tools\services\CommandService;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractGenerateCommand extends Command {

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
	protected function getService() {
		return $this->service;
	}


	protected function configure() {
// 		$this
// 			->addOption(
// 				'schema',
// 				's',
// 				InputOption::VALUE_OPTIONAL,
// 				'Path to the database schema (if ommited, database/schema.xml is used)',
// 				null
// 			)
// 			->addOption(
// 				'force',
// 				'f',
// 				InputOption::VALUE_OPTIONAL,
// 				'Forces to owerwrite',
// 				false
// 			)
// 		;
		
		$this->configureGlobalOptions();
	}
	
	protected function configureGenerateOptions() {
		$this
			->addOption(
				'schema',
				's',
				InputOption::VALUE_OPTIONAL,
				'Path to the database schema (if ommited, database/schema.xml is used)',
				null
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
	
	protected function runCommand($name, InputInterface $input, OutputInterface $output) {
		// return whether command has already executed
		$app = $this->getApplication();
		if ($app->commandRan($name)) {
			return;
		}
	
		$args = ['command' => $name];
		$command = $app->find($name);
		$definition = $command->getDefinition();
		
		// arguments
		foreach ($definition->getArguments() as $arg) {
			$key = $arg->getName();
			if ($input->hasArgument($key)) {
				$args[$key] = $input->getArgument($key);
			}
		}
		
		// options
		foreach ($definition->getOptions() as $option) {
			$key = $option->getName();
			if ($input->hasOption($key)) {
				$args['--' . $key] = $input->getOption($key);
			}
		}
	
		$input = new ArrayInput($args);
		$input->setInteractive(false);
	
		try {
			$exitCode = $command->run($input, $output);
	
			$event = new ConsoleTerminateEvent($command, $input, $output, $exitCode);
			$app->getDispatcher()->dispatch(ConsoleEvents::TERMINATE, $event);
		} catch (\Exception $e) {
			$event = new ConsoleExceptionEvent($command, $input, $output, $e, 1);
			$app->getDispatcher()->dispatch(ConsoleEvents::EXCEPTION, $event);
	
			throw $event->getException();
		}
	
		return $exitCode;
	}

}