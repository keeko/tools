<?php
namespace keeko\tools\generator\name;

use keeko\tools\services\CommandService;

abstract class AbstractNameGenerator {

	/** @var CommandService */
	protected $service;

	public function __construct(CommandService $service) {
		$this->service = $service;
	}

}
