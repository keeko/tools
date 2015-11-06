<?php
namespace keeko\tools\generator;

use keeko\tools\services\CommandService;

class GeneratorFactory {
	
	/**
	 * Creates a generator for the given trait type
	 * 
	 * @param string $type
	 * @return AbstractTraitGenerator
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
}