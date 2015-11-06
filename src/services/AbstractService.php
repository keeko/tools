<?php
namespace keeko\tools\services;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use keeko\tools\helpers\ServiceLoaderTrait;

class AbstractService {
	
	use ServiceLoaderTrait;
	
	public function __construct() {
		
	}
	
	public function setService(CommandService $service) {
		$this->loadServices($service);
	}

}