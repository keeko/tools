<?php
namespace keeko\tools\services;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractService {
	
	protected $service;
	protected $io;
	
	public function __construct(CommandService $service) {
		$this->service = $service;
		$this->io = $service->getIOService();
	}

	/**
	 * @return InputInterface
	 */
	protected function getInput() {
		return $this->io->getInput();
	}

	/**
	 * @return OutputInterface
	 */
	protected function getOutput() {
		return $this->io->getOutput();
	}
	
	/**
	 * @return CommandService
	 */
	protected function getService() {
		return $this->service;
	}
}