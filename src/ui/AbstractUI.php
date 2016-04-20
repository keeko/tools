<?php
namespace keeko\tools\ui;

use keeko\tools\helpers\QuestionHelperTrait;
use keeko\tools\services\IOService;
use Symfony\Component\Console\Command\Command;
use keeko\tools\command\AbstractKeekoCommand;
use keeko\tools\services\CommandService;

abstract class AbstractUI {
	
	use QuestionHelperTrait;
	
	/** @var AbstractKeekoCommand */
	protected $command;
	
	/** @var IOService */
	protected $io;
	
	public function __construct(AbstractKeekoCommand $command) {
		$this->command = $command;
		$this->io = $command->getService()->getIOService();
	}
	
	abstract public function show();
	
	/**
	 * @return HelperSet
	 */
	protected function getHelperSet() {
		return $this->command->getHelperSet();
	}
	
	/**
	 * @return CommandService
	 */
	protected function getService() {
		return $this->command->getService();
	}
}