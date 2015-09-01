<?php
namespace keeko\tools\helpers;

use keeko\tools\services\CommandService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait IOServiceTrait {

	/**
	 * @return CommandService
	 */
	abstract protected function getService();
	
	/**
	 * @return InputInterface
	 */
	public function getInput() {
		return $this->getService()->getIOService()->getInput();
	}
	
	/**
	 * @return OutputInterface 
	 */
	public function getOutput() {
		return $this->getService()->getIOService()->getOutput();
	}
	
	public function writeln($message) {
		$this->getService()->getIOService()->writeln($message);
	}
}