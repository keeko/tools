<?php
namespace keeko\tools\generator\api;

use keeko\tools\generator\AbstractGenerator;
use gossi\swagger\Swagger;
use keeko\tools\services\CommandService;
use gossi\swagger\collections\Definitions;

class ApiGenerator extends AbstractGenerator {

	private $operationGenerator;

	private $definitionGenerator;

	private $needsPaginationDefinition = false;

	private $needsResourceIdentifier = false;

	public function __construct(CommandService $service) {
		parent::__construct($service);
		$this->operationGenerator = new ApiOperationGenerator($service);
		$this->definitionGenerator = new ApiDefinitionGenerator($service);
	}

	public function generatePaths(Swagger $swagger) {
		$paths = $swagger->getPaths();

		foreach ($this->packageService->getModule()->getActionNames() as $name) {
			$this->operationGenerator->generateOperation($paths, $name);
		}
	}

	public function generateDefinitions(Swagger $swagger) {
		$definitions = $swagger->getDefinitions();

		foreach ($this->modelService->getModels() as $model) {
			$this->definitionGenerator->generate($definitions, $model);
			$this->needsPaginationDefinition = $this->needsPaginationDefinition || $this->definitionGenerator->needsPaginationDefinition();
			$this->needsResourceIdentifier = $this->needsResourceIdentifier || $this->definitionGenerator->needsResourceIdentifier();
		}

		// general definitions
		$this->generateErrorDefinition($definitions);
		$this->generatePaginationDefinitions($definitions);
		$this->generateResourceIdentifier($definitions);
	}

	protected function generateErrorDefinition(Definitions $definitions) {
		$definitions->get('Errors')->setType('array')->getItems()->setRef('#/definitions/Error');

		$error = $definitions->get('Error')->setType('object')->getProperties();
		$error->get('id')->setType('string');
		$error->get('status')->setType('string');
		$error->get('code')->setType('string');
		$error->get('title')->setType('string');
		$error->get('detail')->setType('string');
		$error->get('meta')->setType('object');
	}

	protected function generatePaginationDefinitions(Definitions $definitions) {
		if ($this->needsPaginationDefinition) {
			$meta = $definitions->get('PaginationMeta')->setType('object')->getProperties();
			$meta->get('total')->setType('integer');

			$links = $definitions->get('PaginationLinks')->setType('object')->getProperties();
			$names = ['first', 'next', 'previous', 'last'];
			foreach ($names as $name) {
				$links->get($name)->setType('string');
			}
		}
	}

	protected function generateResourceIdentifier(Definitions $definitions) {
		if ($this->needsResourceIdentifier) {
			$props = $definitions->get('ResourceIdentifier')->setType('object')->getProperties();
			$props->get('id')->setType('string');
			$props->get('type')->setType('string');
		}
	}
}