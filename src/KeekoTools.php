<?php
namespace keeko\tools;

use keeko\tools\command\GenerateActionCommand;
use keeko\tools\command\GenerateApiCommand;
use keeko\tools\command\GenerateDomainCommand;
use keeko\tools\command\GenerateEmberAbilitiesCommand;
use keeko\tools\command\GenerateEmberModelsCommand;
use keeko\tools\command\GenerateModelsCommand;
use keeko\tools\command\GenerateResponderCommand;
use keeko\tools\command\GenerateSerializerCommand;
use keeko\tools\command\InitCommand;
use keeko\tools\command\MagicCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use keeko\tools\command\GenerateEmberSerializerCommand;
use keeko\tools\command\GenerateEmberCommand;

class KeekoTools extends Application {

	protected $finishedCommands = [];

	protected $keekoDispatcher = null;

	/* (non-PHPdoc)
	 * @see \Symfony\Component\Console\Application::__construct()
	*/
	public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN') {
		parent::__construct($name, $version);

		$dispatcher = new EventDispatcher();
		$this->setDispatcher($dispatcher);
		$this->keekoDispatcher = $dispatcher;

		$dispatcher->addListener(ConsoleEvents::TERMINATE, function(ConsoleTerminateEvent $event) {
			$command = $event->getCommand();
			$this->finishedCommands[] = $command->getName();
		});
	}

	protected function getDefaultCommands() {
		$cmds = parent::getDefaultCommands();
		$cmds[] = new InitCommand();
		$cmds[] = new GenerateModelsCommand();
		$cmds[] = new GenerateActionCommand();
		$cmds[] = new GenerateDomainCommand();
		$cmds[] = new GenerateSerializerCommand();
		$cmds[] = new GenerateResponderCommand();
		$cmds[] = new GenerateApiCommand();
		$cmds[] = new GenerateEmberModelsCommand();
		$cmds[] = new GenerateEmberAbilitiesCommand();
		$cmds[] = new GenerateEmberSerializerCommand();
		$cmds[] = new GenerateEmberCommand();
		$cmds[] = new MagicCommand();

		return $cmds;
	}

	public function commandRan($name) {
		return in_array($name, $this->finishedCommands);
	}

	public function getDispatcher() {
		return $this->keekoDispatcher;
	}
}
