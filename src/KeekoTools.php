<?php
namespace keeko\tools;

use Symfony\Component\Console\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;
use keeko\tools\command\GenerateActionCommand;
use keeko\tools\command\GenerateResponseCommand;
use keeko\tools\command\MagicCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use keeko\tools\command\GenerateApiCommand;
use keeko\tools\command\InitCommand;


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

		$dispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleTerminateEvent $event) {
			$command = $event->getCommand();
			$this->finishedCommands[] = $command->getName();
		});
	}
	
	protected function getDefaultCommands() {
		$cmds = parent::getDefaultCommands();
		$cmds[] = new InitCommand();
		$cmds[] = new GenerateActionCommand();
		$cmds[] = new GenerateResponseCommand();
		$cmds[] = new GenerateApiCommand();
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
