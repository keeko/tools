<?php
namespace keeko\tools\generator\api;

use gossi\swagger\collections\Paths;
use keeko\framework\utils\NameUtils;
use keeko\tools\generator\AbstractGenerator;
use keeko\tools\generator\Types;
use keeko\tools\services\CommandService;
use phootwork\lang\Text;

class ApiOperationGenerator extends AbstractGenerator {

	private $generators;

	public function __construct(CommandService $service) {
		parent::__construct($service);

		$this->generators = [
			Types::PAGINATE => new ApiPaginateOperationGenerator($service),
			Types::CREATE => new ApiCreateOperationGenerator($service),
			Types::READ => new ApiReadOperationGenerator($service),
			Types::UPDATE => new ApiUpdateOperationGenerator($service),
			Types::DELETE => new ApiDeleteOperationGenerator($service)
		];
	}

	public function generateOperation(Paths $paths, $actionName) {
		$this->logger->notice('Generating Operation for: ' . $actionName);

		if (Text::create($actionName)->contains('relationship')) {
			$this->generateRelationshipOperation($paths, $actionName);
		} else {
			$this->generateCRUDOperation($paths, $actionName);
		}
	}

	protected function generateCRUDOperation(Paths $paths, $actionName) {
		$this->logger->notice('Generating CRUD Operation for: ' . $actionName);

		$action = $this->packageService->getAction($actionName);
		$modelName = $this->modelService->getModelNameByAction($action);
		$type = $this->packageService->getActionType($actionName, $modelName);

		if (isset($this->generators[$type])) {
			$this->generators[$type]->generate($paths, $actionName);
		}
	}

	protected function generateRelationshipOperation(Paths $paths, $actionName) {
		$this->logger->notice('Generating Relationship Operation for: ' . $actionName);
		$parsed = $this->factory->getActionNameGenerator()->parseRelationship($actionName);
		$type = $parsed['type'];
		$modelName = $parsed['modelName'];
		$model = $this->modelService->getModel($modelName);
		$relatedTypeName = NameUtils::dasherize($parsed['relatedName']);
		$relationship = $this->modelService->getRelationship($model, $relatedTypeName);

		if ($relationship === null) {
			return;
		}

		// see if either one of them is excluded
		$relatedName = $relationship->getRelatedName();
		$foreignModelName = $relationship->getForeign()->getOriginCommonName();
		$excluded = $this->project->getGeneratorDefinition()->getExcludedApi();
		if ($excluded->contains($modelName) || $excluded->contains($foreignModelName)) {
			return;
		}

		// continue if neither model nor related model is excluded
		$action = $this->packageService->getAction($actionName);
		$method = $this->getMethod($type);
		$endpoint = '/' . NameUtils::pluralize(NameUtils::dasherize($modelName)) . '/{id}/relationship/' .
			NameUtils::dasherize($relationship->isOneToOne()
				? $relatedTypeName
				: NameUtils::pluralize($relatedTypeName));

		$path = $paths->get($endpoint);
		$method = $this->getMethod($type);
		$operation = $path->getOperation($method);
		$operation->setDescription($action->getTitle());
		$operation->setOperationId($action->getName());
// 		$operation->getTags()->clear();
// 		$operation->getTags()->add(new Tag($this->package->getKeeko()->getModule()->getSlug()));

		$params = $operation->getParameters();
		$responses = $operation->getResponses();

		// general model related params
		// params
		$id = $params->getByName('id');
		$id->setIn('path');
		$id->setDescription(sprintf('The %s id', $modelName));
		$id->setRequired(true);
		$id->setType('integer');

		if ($type == Types::ADD || $type == Types::UPDATE) {
			$body = $params->getByName('body');
			$body->setName('body');
			$body->setIn('body');
			$body->setDescription(sprintf('%ss %s', ucfirst($type), $relatedName));
			$body->setRequired(true);

			$props = $body->getSchema()->setType('object')->getProperties();
			$data = $props->get('data');

			if ($relationship->isOneToOne()) {
				$data->setRef('#/definitions/ResourceIdentifier');
			} else {
				$data
				->setType('array')
				->getItems()->setRef('#/definitions/ResourceIdentifier');
			}
		}

		// response
		$ok = $responses->get('200');
		$ok->setDescription('Retrieve ' . $relatedName . ' from ' . $modelName);
		$props = $ok->getSchema()->setType('object')->getProperties();
		$links = $props->get('links')->setType('object')->getProperties();
		$links->get('self')->setType('string');
		if ($relationship->isOneToOne()) {
			$links->get('related')->setType('string');
		}
		$data = $props->get('data');
		if ($relationship->isOneToOne()) {
			$data->setType('object')->setRef('#/definitions/ResourceIdentifier');
		} else {
			$data
			->setType('array')
			->getItems()->setRef('#/definitions/ResourceIdentifier');
		}

		$invalid = $responses->get('400');
		$invalid->setDescription('Invalid ID supplied');
		$invalid->getSchema()->setRef('#/definitions/Errors');

		$notfound = $responses->get('404');
		$notfound->setDescription(sprintf('No %s found', $modelName));
		$notfound->getSchema()->setRef('#/definitions/Errors');
	}

	private function getMethod($type) {
		$methods = [
			Types::PAGINATE => 'get',
			Types::CREATE => 'post',
			Types::READ => 'get',
			Types::UPDATE => 'patch',
			Types::DELETE => 'delete',
			Types::ADD => 'post',
			Types::REMOVE => 'delete'
		];

		return $methods[$type];
	}

}