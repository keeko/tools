<?php
namespace keeko\tools\command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use CG\Model\PhpClass;
use CG\Model\PhpMethod;
use CG\Model\PhpParameter;
use keeko\tools\utils\NameUtils;
use Symfony\Component\Console\Input\InputArgument;
use Propel\Generator\Model\Table;
use Symfony\Component\Console\Command\Command;

class GenerateInitResponseCommand extends AbstractGenerateCommand {
	
	protected function configure() {
		$this
			->setName('generate:init-response')
			->setDescription('Initializes the project\'s responses in composer.json')
		;
		
		self::configureParameters($this);

		parent::configure();
	}
	
	public static function configureParameters(Command $command) {
		$command = GenerateInitActionCommand::configureParameters($command);
		return $command
			->addOption(
				'format',
				'',
				InputOption::VALUE_OPTIONAL,
				'The response format to create',
				'json'
			)
		;
	}
	
	public function getOptionKeys() {
		$keys = array_merge(['format'], parent::getOptionKeys());
		
		// get keys from dependent commands
		$command = $this->getApplication()->find('generate:init-action');
		
		return array_merge($keys, $command->getOptionKeys());
	}
	
	public function getArgumentKeys() {
		$keys = array_merge([], parent::getArgumentKeys());
		
		// get keys from dependent commands
		$command = $this->getApplication()->find('generate:init-action');
		
		return array_merge($keys, $command->getArgumentKeys());
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->runCommand('generate:init-action', $input, $output);

		$package = $this->getPackage($input);
		$actions = $this->getKeekoActions($input);
		$format = $input->getOption('format');
		$force = $input->getOption('force');

		foreach ($actions as $name => $action) {
			$actionClass = $action['class'];

			if (isset($action['response'])) {
				$response = $action['response'];
			} else {
				$response = [];
			}

			if (!isset($response[$format]) || $force) {
				$class = str_replace(['action', 'Action'], ['response', ucfirst($format) . 'Response'], $actionClass);
				$response[$format] = $class;
			}
			
			$action['response'] = $response;
			$actions[$name] = $action;
		}
		
		$package['extra']['keeko']['module']['actions'] = $actions;
		
		$this->saveComposer($package, $input, $output);
	}

}
