<?php
namespace keeko\tools\generator;

use keeko\tools\generator\action\AbstractModelActionGenerator;
use keeko\tools\generator\action\ModelCreateActionGenerator;
use keeko\tools\generator\action\ModelDeleteActionGenerator;
use keeko\tools\generator\action\ModelListActionGenerator;
use keeko\tools\generator\action\ModelReadActionGenerator;
use keeko\tools\generator\action\ModelUpdateActionGenerator;
use keeko\tools\generator\package\AbstractPackageGenerator;
use keeko\tools\generator\package\AppPackageGenerator;
use keeko\tools\generator\package\ModulePackageGenerator;
use keeko\tools\generator\responder\AbstractModelJsonResponderGenerator;
use keeko\tools\generator\responder\ModelCreateJsonResponderGenerator;
use keeko\tools\generator\responder\ModelDeleteJsonResponderGenerator;
use keeko\tools\generator\responder\ModelListJsonResponderGenerator;
use keeko\tools\generator\responder\ModelReadJsonResponderGenerator;
use keeko\tools\generator\responder\ModelUpdateJsonResponderGenerator;
use keeko\tools\generator\responder\PayloadHtmlResponderGenerator;
use keeko\tools\generator\responder\PayloadJsonResponderGenerator;
use keeko\tools\services\CommandService;
use keeko\tools\model\Relationship;
use keeko\tools\generator\action\AbstractActionGenerator;
use keeko\tools\generator\action\ToOneRelationshipReadActionGenerator;
use keeko\tools\generator\action\ToOneRelationshipUpdateActionGenerator;
use keeko\tools\generator\action\ToManyRelationshipReadActionGenerator;
use keeko\tools\generator\action\ToManyRelationshipUpdateActionGenerator;
use keeko\tools\generator\action\ToManyRelationshipAddActionGenerator;
use keeko\tools\generator\action\ToManyRelationshipRemoveActionGenerator;

class GeneratorFactory {
	
	private $service;
	
	public function __construct(CommandService $service) {
		$this->service = $service;
	}
	
	/**
	 * Creates a generator for a relationship action
	 * 
	 * @param string $type
	 * @param Relationship $relationship
	 * @return AbstractActionGenerator
	 */
	public function createActionRelationshipGenerator($type, Relationship $relationship) {
		if ($relationship->getType() == Relationship::ONE_TO_ONE) {
			switch ($type) {
				case 'read':
					return new ToOneRelationshipReadActionGenerator($this->service);
					
				case 'update':
					return new ToOneRelationshipUpdateActionGenerator($this->service);
			}
		} else {
			switch ($type) {
				case 'read':
					return new ToManyRelationshipReadActionGenerator($this->service);
					
				case 'update':
					return new ToManyRelationshipUpdateActionGenerator($this->service);
					
				case 'add':
					return new ToManyRelationshipAddActionGenerator($this->service);
					
				case 'remove':
					return new ToManyRelationshipRemoveActionGenerator($this->service);
			}
		}
	}
	
	/**
	 * Creates a generator for the given trait type
	 * 
	 * @param string $type
	 * @return AbstractModelActionGenerator
	 */
	public function createModelActionGenerator($type) {
		switch ($type) {
			case 'list':
				return new ModelListActionGenerator($this->service);
				
			case 'create':
				return new ModelCreateActionGenerator($this->service);
				
			case 'update':
				return new ModelUpdateActionGenerator($this->service);
				
			case 'read':
				return new ModelReadActionGenerator($this->service);
				
			case 'delete':
				return new ModelDeleteActionGenerator($this->service);
		}
	}
	
	/**
	 * Creates a generator for the given json respose
	 * 
	 * @param string $type
	 * @param CommandService $this->service
	 * @return AbstractModelJsonResponderGenerator
	 */
	public function createModelJsonResponderGenerator($type) {
		switch ($type) {
			case 'list':
				return new ModelListJsonResponderGenerator($this->service);

			case 'create':
				return new ModelCreateJsonResponderGenerator($this->service);
		
			case 'update':
				return new ModelUpdateJsonResponderGenerator($this->service);
		
			case 'read':
				return new ModelReadJsonResponderGenerator($this->service);
		
			case 'delete':
				return new ModelDeleteJsonResponderGenerator($this->service);
		}
	}

	/**
	 * Creates a new package generator
	 * 
	 * @param string $type
	 * @param CommandService $this->serivce
	 * @return AbstractPackageGenerator
	 */
	public function createPackageGenerator($type) {
		switch ($type) {
			case 'app':
				return new AppPackageGenerator($this->service);
				
			case 'module':
				return new ModulePackageGenerator($this->service);
		}
	}
	
	public function createPayloadGenerator($format) {
		switch ($format) {
			case 'json':
				return new PayloadJsonResponderGenerator($this->service);
				
			case 'html':
				return new PayloadHtmlResponderGenerator($this->service);
		}
	}
	
}
