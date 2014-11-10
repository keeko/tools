<?php
namespace keeko\tools\helpers;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait BaseHelperTrait {
	
	/**
	 * @return HelperSet
	 */
	abstract protected function getHelperSet();
	
	abstract protected function getName();
	
	/**
	 * @return InputInterface
	 */
	protected function getInput() {
		return $this->getHelperSet()->get('io')->getInput();
	}
	
	/**
	 * @return OutputInterface
	 */
	protected function getOutput() {
		return $this->getHelperSet()->get('io')->getOutput();
	}
	
	protected function writeln($message) {
		$formatter = $this->getHelperSet()->get('formatter');
		$line = $formatter->formatSection($this->getName(), $message);
		$this->getOutput()->writeln($line);
	}

}