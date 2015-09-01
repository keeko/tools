<?php
namespace keeko\tools\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Propel\Generator\Manager\ModelManager;
use Symfony\Component\Console\Output\OutputInterface;
use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Model\Database;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\ArrayInput;
use keeko\tools\utils\NameUtils;
use Propel\Generator\Model\Table;
use gossi\docblock\tags\AuthorTag;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use gossi\codegen\generator\CodeGenerator;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\AbstractPhpStruct;
use keeko\tools\helpers\IOHelper;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Psr\Log\LogLevel;
use keeko\tools\services\CommandService;
use keeko\tools\services\IOService;
use keeko\tools\model\Project;
use keeko\core\schema\PackageSchema;
use keeko\tools\helpers\PackageServiceTrait;
use keeko\tools\helpers\IOServiceTrait;

abstract class AbstractGenerateCommand extends Command {

	use PackageServiceTrait;
	use IOServiceTrait;
	
	protected $templateRoot;
	protected $logger;
	protected $service;

	/** @var Project */
	protected $project;

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
	
		// services
		$this->service = new CommandService($this);
		$this->project = $this->service->getProject();
		$this->package = $this->service->getPackageService()->getPackage();
		
		// logger
		$this->logger = new ConsoleLogger($output, [
			LogLevel::NOTICE => OutputInterface::VERBOSITY_VERBOSE,
			LogLevel::INFO   => OutputInterface::VERBOSITY_VERBOSE,
		]);
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