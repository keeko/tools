<?php

namespace keeko\tools\helpers;

use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 * @author thomas
 *        
 */
class IOHelper implements HelperInterface {
	
	/** @var HelperSet */
	private $helpers;
	
	/** @var InputInterface */
	private $input;
	
	/** @var OutputInterface */
	private $output;
	
	/*
	 * (non-PHPdoc)
	 * @see \Symfony\Component\Console\Helper\HelperInterface::getName()
	 */
	public function getName() {
		return 'io';
	}
	
	/*
	 * (non-PHPdoc)
	 * @see \Symfony\Component\Console\Helper\HelperInterface::setHelperSet()
	 */
	public function setHelperSet(HelperSet $helperSet = null) {
		$this->helpers = $helperSet;
	}
	
	/*
	 * (non-PHPdoc)
	 * @see \Symfony\Component\Console\Helper\HelperInterface::getHelperSet()
	 */
	public function getHelperSet() {
		return $this->helpers;
	}
	
	/**
	 *
	 * @return the InputInterface
	 */
	public function getInput() {
		return $this->input;
	}
	
	/**
	 *
	 * @param InputInterface $input        	
	 */
	public function setInput(InputInterface $input) {
		$this->input = $input;
		return $this;
	}
	
	/**
	 *
	 * @return the OutputInterface
	 */
	public function getOutput() {
		return $this->output;
	}
	
	/**
	 *
	 * @param OutputInterface $output        	
	 */
	public function setOutput(OutputInterface $output) {
		$this->output = $output;
		return $this;
	}
	
	
	
}