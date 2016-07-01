<?php
namespace keeko\tools\generator\api;

use keeko\tools\generator\AbstractGenerator;
use keeko\framework\utils\NameUtils;
use keeko\tools\generator\Types;
use gossi\swagger\collections\Paths;
use gossi\swagger\collections\Responses;
use gossi\swagger\collections\Parameters;
use Propel\Generator\Model\Table;

class ApiCrudOperationGenerator extends AbstractGenerator {

	public function generate(Paths $paths, $actionName) {
		$action = $this->packageService->getAction($actionName);
		$modelName = $this->modelService->getModelNameByAction($action);
		$model = $this->modelService->getModel($modelName);

		if ($model === null) {
			return;
		}

		$generatorDefinition = $this->project->getGeneratorDefinition();
		if ($generatorDefinition->getExcludedApi()->contains($modelName)) {
			return;
		}

		$type = $this->packageService->getActionType($actionName, $modelName);
		if (!Types::isModelType($type)) {
			throw new \RuntimeException(sprintf('Unknown type (%s).', $type));
		}

		$modelPluralName = NameUtils::pluralize($modelName);

		// find path branch
		switch ($type) {
			case Types::PAGINATE:
			case Types::CREATE:
				$endpoint = '/' . NameUtils::dasherize($modelPluralName);
				break;

			case Types::READ:
			case Types::UPDATE:
			case Types::DELETE:
				$endpoint = '/' . NameUtils::dasherize($modelPluralName) . '/{id}';
				break;
		}

		$path = $paths->get($endpoint);
		$method = $this->getMethod($type);
		$operation = $path->getOperation($method);
		$operation->setDescription($action->getTitle());
		$operation->setOperationId($action->getName());
// 		$operation->getTags()->clear();
// 		$operation->getTags()->add(new Tag($this->package->getKeeko()->getModule()->getSlug()));

		$this->generateParams($operation->getParameters(), $model);
		$this->generateResponses($operation->getResponses(), $model);
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

	protected function generateParams(Parameters $params, Table $model) {
		// Overwrite this method in a subclass to provide functionality
	}

	protected function generateResponses(Responses $responses, Table $model) {
		// Overwrite this method in a subclass to provide functionality
	}

	protected function generateIdParam(Parameters $params, Table $model) {
		$id = $params->getByName('id');
		$id->setIn('path');
		$id->setDescription(sprintf('The %s id', $model->getOriginCommonName()));
		$id->setRequired(true);
		$id->setType('integer');
	}

	protected function generateInvalidResponse(Responses $responses) {
		$invalid = $responses->get('400');
		$invalid->setDescription('Invalid ID supplied');
		$invalid->getSchema()->setRef('#/definitions/Errors');
	}

	protected function generateNotFoundResponse(Responses $responses, Table $model) {
		$notfound = $responses->get('404');
		$notfound->setDescription(sprintf('No %s found', $model->getOriginCommonName()));
		$notfound->getSchema()->setRef('#/definitions/Errors');
	}
}