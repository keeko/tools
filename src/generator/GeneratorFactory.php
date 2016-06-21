<?php
namespace keeko\tools\generator;

use keeko\tools\generator\action\AbstractActionGenerator;
use keeko\tools\generator\action\AbstractModelActionGenerator;
use keeko\tools\generator\action\ModelCreateActionGenerator;
use keeko\tools\generator\action\ModelDeleteActionGenerator;
use keeko\tools\generator\action\ModelPaginateActionGenerator;
use keeko\tools\generator\action\ModelReadActionGenerator;
use keeko\tools\generator\action\ModelUpdateActionGenerator;
use keeko\tools\generator\action\ToManyRelationshipAddActionGenerator;
use keeko\tools\generator\action\ToManyRelationshipReadActionGenerator;
use keeko\tools\generator\action\ToManyRelationshipRemoveActionGenerator;
use keeko\tools\generator\action\ToManyRelationshipUpdateActionGenerator;
use keeko\tools\generator\action\ToOneRelationshipReadActionGenerator;
use keeko\tools\generator\action\ToOneRelationshipUpdateActionGenerator;
use keeko\tools\generator\name\ActionClassNameGenerator;
use keeko\tools\generator\name\ActionNameGenerator;
use keeko\tools\generator\name\ActionTitleGenerator;
use keeko\tools\generator\name\NamespaceGenerator;
use keeko\tools\generator\name\ResponderClassNameGenerator;
use keeko\tools\generator\package\AbstractPackageGenerator;
use keeko\tools\generator\package\AppPackageGenerator;
use keeko\tools\generator\package\ModulePackageGenerator;
use keeko\tools\generator\responder\AbstractModelJsonResponderGenerator;
use keeko\tools\generator\responder\AbstractResponderGenerator;
use keeko\tools\generator\responder\ModelCreateJsonResponderGenerator;
use keeko\tools\generator\responder\ModelDeleteJsonResponderGenerator;
use keeko\tools\generator\responder\ModelPaginateJsonResponderGenerator;
use keeko\tools\generator\responder\ModelReadJsonResponderGenerator;
use keeko\tools\generator\responder\ModelUpdateJsonResponderGenerator;
use keeko\tools\generator\responder\PayloadHtmlResponderGenerator;
use keeko\tools\generator\responder\PayloadJsonResponderGenerator;
use keeko\tools\generator\responder\ToManyRelationshipJsonResponderGenerator;
use keeko\tools\generator\responder\ToOneRelationshipJsonResponderGenerator;
use keeko\tools\model\Relationship;
use keeko\tools\services\CommandService;
use keeko\tools\generator\name\RelationshipMethodNameGenerator;

class GeneratorFactory {

	/** @var CommandService */
	private $service;

	/** @var ActionNameGenerator */
	private $actionNameGenerator;

	/** @var ActionClassNameGenerator */
	private $actionClassNameGenerator;

	/** @var ActionTitleGenerator */
	private $actionTitleGenerator;

	/** @var NamespaceGenerator */
	private $namespaceGenerator;

	/** @var ResponderClassNameGenerator */
	private $responderClassNameGenerator;

	/** @var RelationshipMethodNameGenerator */
	private $relationshipMethodNameGenerator;

	public function __construct(CommandService $service) {
		$this->service = $service;
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

	/**
	 * Creates a generator for the given trait type
	 *
	 * @param string $type
	 * @return AbstractModelActionGenerator
	 */
	public function createModelActionGenerator($type) {
		switch ($type) {
			case Types::PAGINATE:
				return new ModelPaginateActionGenerator($this->service);

			case Types::CREATE:
				return new ModelCreateActionGenerator($this->service);

			case Types::UPDATE:
				return new ModelUpdateActionGenerator($this->service);

			case Types::READ:
				return new ModelReadActionGenerator($this->service);

			case Types::DELETE:
				return new ModelDeleteActionGenerator($this->service);
		}
	}

	/**
	 * Creates a generator for a relationship action
	 *
	 * @param string $type
	 * @param Relationship $relationship
	 * @return AbstractActionGenerator
	 */
	public function createRelationshipActionGenerator($type, Relationship $relationship) {
		if ($relationship->getType() == Relationship::ONE_TO_ONE) {
			switch ($type) {
				case Types::READ:
					return new ToOneRelationshipReadActionGenerator($this->service);

				case Types::UPDATE:
					return new ToOneRelationshipUpdateActionGenerator($this->service);
			}
		} else {
			switch ($type) {
				case Types::READ:
					return new ToManyRelationshipReadActionGenerator($this->service);

				case Types::UPDATE:
					return new ToManyRelationshipUpdateActionGenerator($this->service);

				case Types::ADD:
					return new ToManyRelationshipAddActionGenerator($this->service);

				case Types::REMOVE:
					return new ToManyRelationshipRemoveActionGenerator($this->service);
			}
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
			case Types::PAGINATE:
				return new ModelPaginateJsonResponderGenerator($this->service);

			case Types::CREATE:
				return new ModelCreateJsonResponderGenerator($this->service);

			case Types::UPDATE:
				return new ModelUpdateJsonResponderGenerator($this->service);

			case Types::READ:
				return new ModelReadJsonResponderGenerator($this->service);

			case Types::DELETE:
				return new ModelDeleteJsonResponderGenerator($this->service);
		}
	}

	/**
	 * Creates a json generator for a relationship
	 *
	 * @param Relationship $relationship
	 * @return AbstractModelJsonResponderGenerator
	 */
	public function createRelationshipJsonResponderGenerator(Relationship $relationship) {
		return $relationship->getType() == Relationship::ONE_TO_ONE
			? new ToOneRelationshipJsonResponderGenerator($this->service, $relationship)
			: new ToManyRelationshipJsonResponderGenerator($this->service, $relationship);
	}

	/**
	 * Creates a payload responder for the given format
	 *
	 * @param string $format
	 * @return AbstractResponderGenerator
	 */
	public function createPayloadResponderGenerator($format) {
		switch ($format) {
			case 'json':
				return new PayloadJsonResponderGenerator($this->service);

			case 'html':
				return new PayloadHtmlResponderGenerator($this->service);
		}
	}

	/**
	 * @return ActionNameGenerator
	 */
	public function getActionNameGenerator() {
		if ($this->actionNameGenerator === null) {
			$this->actionNameGenerator = new ActionNameGenerator($this->service);
		}

		return $this->actionNameGenerator;
	}

	/**
	 * @return ActionClassNameGenerator
	 */
	public function getActionClassNameGenerator() {
		if ($this->actionClassNameGenerator === null) {
			$this->actionClassNameGenerator = new ActionClassNameGenerator($this->service);
		}

		return $this->actionClassNameGenerator;
	}

	/**
	 * @return ActionTitleGenerator
	 */
	public function getActionTitleGenerator() {
		if ($this->actionTitleGenerator === null) {
			$this->actionTitleGenerator = new ActionTitleGenerator($this->service);
		}

		return $this->actionTitleGenerator;
	}

	/**
	 * @return NamespaceGenerator
	 */
	public function getNamespaceGenerator() {
		if ($this->namespaceGenerator === null) {
			$this->namespaceGenerator = new NamespaceGenerator($this->service);
		}

		return $this->namespaceGenerator;
	}

	/**
	 * @return ResponderClassNameGenerator
	 */
	public function getResponderClassNameGenerator() {
		if ($this->responderClassNameGenerator === null) {
			$this->responderClassNameGenerator = new ResponderClassNameGenerator($this->service);
		}

		return $this->responderClassNameGenerator;
	}

	/**
	 * @return RelationshipMethodNameGenerator
	 */
	public function getRelationshipMethodNameGenerator() {
		if ($this->relationshipMethodNameGenerator === null) {
			$this->relationshipMethodNameGenerator = new RelationshipMethodNameGenerator($this->service);
		}

		return $this->relationshipMethodNameGenerator;
	}
}
