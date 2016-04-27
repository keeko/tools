<?php
namespace keeko\tools\generator;

use keeko\tools\generator\action\AbstractModelActionGenerator;
use keeko\tools\generator\action\ModelCreateActionGenerator;
use keeko\tools\generator\action\ModelDeleteActionGenerator;
use keeko\tools\generator\action\ModelListActionGenerator;
use keeko\tools\generator\action\ModelReadActionGenerator;
use keeko\tools\generator\action\ModelUpdateActionGenerator;
use keeko\tools\generator\responder\AbstractPayloadJsonResponderGenerator;
use keeko\tools\generator\responder\ModelCreateJsonResponderGenerator;
use keeko\tools\generator\responder\ModelDeleteJsonResponderGenerator;
use keeko\tools\generator\responder\ModelListJsonResponderGenerator;
use keeko\tools\generator\responder\ModelReadJsonResponderGenerator;
use keeko\tools\generator\responder\ModelUpdateJsonResponderGenerator;
use keeko\tools\services\CommandService;
use keeko\tools\generator\package\AppPackageGenerator;
use keeko\tools\generator\package\ModulePackageGenerator;
use keeko\tools\generator\package\AbstractPackageGenerator;
use keeko\tools\generator\responder\PayloadJsonResponderGenerator;
use keeko\tools\generator\responder\PayloadHtmlResponderGenerator;

class GeneratorFactory {
	
	/**
	 * Creates a generator for the given trait type
	 * 
	 * @param string $type
	 * @return AbstractModelActionGenerator
	 */
	public static function createModelActionGenerator($type, CommandService $service) {
		switch ($type) {
			case 'list':
				return new ModelListActionGenerator($service);
				
			case 'create':
				return new ModelCreateActionGenerator($service);
				
			case 'update':
				return new ModelUpdateActionGenerator($service);
				
			case 'read':
				return new ModelReadActionGenerator($service);
				
			case 'delete':
				return new ModelDeleteActionGenerator($service);
		}
	}
	
	/**
	 * Creates a generator for the given json respose
	 * 
	 * @param string $type
	 * @param CommandService $service
	 * @return AbstractPayloadJsonResponderGenerator
	 */
	public static function createModelJsonResponderGenerator($type, CommandService $service) {
		switch ($type) {
			case 'list':
				return new ModelListJsonResponderGenerator($service);

			case 'create':
				return new ModelCreateJsonResponderGenerator($service);
		
			case 'update':
				return new ModelUpdateJsonResponderGenerator($service);
		
			case 'read':
				return new ModelReadJsonResponderGenerator($service);
		
			case 'delete':
				return new ModelDeleteJsonResponderGenerator($service);
		}
	}

	/**
	 * Creates a new package generator
	 * 
	 * @param string $type
	 * @param CommandService $serivce
	 * @return AbstractPackageGenerator
	 */
	public static function createPackageGenerator($type, CommandService $service) {
		switch ($type) {
			case 'app':
				return new AppPackageGenerator($service);
				
			case 'module':
				return new ModulePackageGenerator($service);
		}
	}
	
	public static function createPayloadGenerator($format, CommandService $service) {
		switch ($format) {
			case 'json':
				return new PayloadJsonResponderGenerator($service);
				
			case 'html':
				return new PayloadHtmlResponderGenerator($service);
		}
	}
}
