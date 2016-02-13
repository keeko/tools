<?php
namespace keeko\tools\generator;

use keeko\tools\generator\action\base\ModelCreateActionTraitGenerator;
use keeko\tools\generator\action\base\ModelDeleteActionTraitGenerator;
use keeko\tools\generator\action\base\ModelListActionTraitGenerator;
use keeko\tools\generator\action\base\ModelReadActionTraitGenerator;
use keeko\tools\generator\action\base\ModelUpdateActionTraitGenerator;
use keeko\tools\generator\response\ModelCreateJsonResponseGenerator;
use keeko\tools\generator\response\ModelDeleteJsonResponseGenerator;
use keeko\tools\generator\response\ModelListJsonResponseGenerator;
use keeko\tools\generator\response\ModelReadJsonResponseGenerator;
use keeko\tools\generator\response\ModelUpdateJsonResponseGenerator;
use keeko\tools\services\CommandService;

class GeneratorFactory {
	
	/**
	 * Creates a generator for the given trait type
	 * 
	 * @param string $type
	 * @return AbstractActionTraitGenerator
	 */
	public static function createActionTraitGenerator($type, CommandService $service) {
		switch ($type) {
			case 'list':
				return new ModelListActionTraitGenerator($service);
				
			case 'create':
				return new ModelCreateActionTraitGenerator($service);
				
			case 'update':
				return new ModelUpdateActionTraitGenerator($service);
				
			case 'read':
				return new ModelReadActionTraitGenerator($service);
				
			case 'delete':
				return new ModelDeleteActionTraitGenerator($service);
		}
	}
	
	/**
	 * Creates a generator for the given json respose
	 * 
	 * @param string $type
	 * @param CommandService $service
	 */
	public static function createJsonResponseGenerator($type, CommandService $service) {
		switch ($type) {
			case 'list':
				return new ModelListJsonResponseGenerator($service);

			case 'create':
				return new ModelCreateJsonResponseGenerator($service);
		
			case 'update':
				return new ModelUpdateJsonResponseGenerator($service);
		
			case 'read':
				return new ModelReadJsonResponseGenerator($service);
		
			case 'delete':
				return new ModelDeleteJsonResponseGenerator($service);
		}
	}

}
