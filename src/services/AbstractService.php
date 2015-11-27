<?php
namespace keeko\tools\services;

use keeko\tools\helpers\ServiceLoaderTrait;

class AbstractService {
	
	use ServiceLoaderTrait;
	
	public function __construct() {
		
	}
	
	public function setService(CommandService $service) {
		$this->loadServices($service);
	}

}