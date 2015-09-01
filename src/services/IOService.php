<?php
namespace keeko\tools\services;

use keeko\tools\helpers\IOHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class IOService {
	
	private $helper;
	private $command;
	
	public function __construct(Command $command) {
		$this->helper = $command->getHelperSet()->get('io');
		$this->command = $command;
	}
	
	/**
	 * @return InputInterface
	 */
	public function getInput() {
		return $this->helper->getInput();
	}
	
	/**
	 * @return OutputInterface
	 */
	public function getOutput() {
		return $this->helper->getOutput();
	}

	public function writeln($message) {
		$formatter = $this->command->getHelperSet()->get('formatter');
		$line = $formatter->formatSection($this->command->getName(), $message);
		$this->getOutput()->writeln($line);
	}
	
}
