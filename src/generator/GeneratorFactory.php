<?php
namespace keeko\tools\generator;

use keeko\tools\generator\action\base\CreateActionTraitGenerator;
use keeko\tools\generator\action\base\DeleteActionTraitGenerator;
use keeko\tools\generator\action\base\ListActionTraitGenerator;
use keeko\tools\generator\action\base\ReadActionTraitGenerator;
use keeko\tools\generator\action\base\UpdateActionTraitGenerator;
use keeko\tools\generator\response\CreateJsonResponseGenerator;
use keeko\tools\generator\response\DeleteJsonResponseGenerator;
use keeko\tools\generator\response\ListJsonResponseGenerator;
use keeko\tools\generator\response\ReadJsonResponseGenerator;
use keeko\tools\generator\response\UpdateJsonResponseGenerator;
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
				return new ListActionTraitGenerator($service);
				
			case 'create':
				return new CreateActionTraitGenerator($service);
				
			case 'update':
				return new UpdateActionTraitGenerator($service);
				
			case 'read':
				return new ReadActionTraitGenerator($service);
				
			case 'delete':
				return new DeleteActionTraitGenerator($service);
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
				return new ListJsonResponseGenerator($service);
		
			case 'create':
				return new CreateJsonResponseGenerator($service);
		
			case 'update':
				return new UpdateJsonResponseGenerator($service);
		
			case 'read':
				return new ReadJsonResponseGenerator($service);
		
			case 'delete':
				return new DeleteJsonResponseGenerator($service);
		}
	}

}
