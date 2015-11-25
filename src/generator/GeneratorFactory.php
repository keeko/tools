<?php
namespace keeko\tools\generator;

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
