<?php
namespace keeko\tools\command;

use gossi\swagger\collections\Definitions;
use gossi\swagger\collections\Paths;
use gossi\swagger\Swagger;
use gossi\swagger\Tag;
use keeko\framework\utils\NameUtils;
use phootwork\file\File;
use phootwork\lang\Text;
use Propel\Generator\Model\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use keeko\tools\model\Relationship;
use keeko\tools\generator\Types;

class GenerateApiCommand extends AbstractKeekoCommand {
	
	private $needsResourceIdentifier = false;
	private $needsPagedMeta = false;

	protected function configure() {
		$this
			->setName('generate:api')
			->setDescription('Generates the api for the module')
			->addOption(
				'model',
				'm',
				InputOption::VALUE_OPTIONAL,
				'The model for which the actions should be generated, when there is no name argument (if ommited all models will be generated)'
			)
		;
		
		$this->configureGenerateOptions();
			
		parent::configure();
	}
	
	/**
	 * Checks whether api can be generated at all by reading composer.json and verify
	 * all required information are available
	 */
	private function check() {
		$module = $this->packageService->getModule();
		if ($module === null) {
			throw new \DomainException('No module definition found in composer.json - please run `keeko init`.');
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->check();
		
		// generate api
		$api = new File($this->project->getApiFileName());
		
		// if generation is forced, generate new API from scratch
		if ($input->getOption('force')) {
			$swagger = new Swagger();
		}
		
		// ... anyway reuse existing one
		else {
			if (!$api->exists()) {
				$api->write('{}');
			}
			
			$swagger = Swagger::fromFile($this->project->getApiFileName());
		}

		$module = $this->package->getKeeko()->getModule();
		$swagger->setVersion('2.0');
		$swagger->getInfo()->setTitle($module->getTitle() . ' API');
		$swagger->getTags()->clear();
		$swagger->getTags()->add(new Tag(['name' => $module->getSlug()]));
		
		$this->generatePaths($swagger);
		$this->generateDefinitions($swagger);
		
		$this->jsonService->write($api->getPathname(), $swagger->toArray());
		$this->io->writeln(sprintf('API for <info>%s</info> written at <info>%s</info>', $this->package->getFullName(), $api->getPathname()));
	}

	protected function generatePaths(Swagger $swagger) {
		$paths = $swagger->getPaths();
		
		foreach ($this->packageService->getModule()->getActionNames() as $name) {
			$this->generateOperation($paths, $name);
		}
	}
	
	protected function generateOperation(Paths $paths, $actionName) {
		$this->logger->notice('Generating Operation for: ' . $actionName);

		if (Text::create($actionName)->contains('relationship')) {
			$this->generateRelationshipOperation($paths, $actionName);
		} else {
			$this->generateCRUDOperation($paths, $actionName);
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
		$excluded = $this->codegenService->getCodegen()->getExcludedApi();
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
	
	protected function generateCRUDOperation(Paths $paths, $actionName) {
		$this->logger->notice('Generating CRUD Operation for: ' . $actionName);
		$database = $this->modelService->getDatabase();
		$action = $this->packageService->getAction($actionName);
		$modelName = $this->modelService->getModelNameByAction($action);
		$tableName = $this->modelService->getTableName($modelName);
		$codegen = $this->codegenService->getCodegen();
	
		if (!$database->hasTable($tableName)) {
			return;
		}
		
		if ($codegen->getExcludedApi()->contains($modelName)) {
			return;
		}
	
		$type = $this->packageService->getActionType($actionName, $modelName);
		$modelObjectName = $database->getTable($tableName)->getPhpName();
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
	
			default:
				throw new \RuntimeException(sprintf('type (%s) not found, can\'t continue.', $type));
				break;
		}

		$path = $paths->get($endpoint);
		$method = $this->getMethod($type);
		$operation = $path->getOperation($method);
		$operation->setDescription($action->getTitle());
		$operation->setOperationId($action->getName());
// 		$operation->getTags()->clear();
// 		$operation->getTags()->add(new Tag($this->package->getKeeko()->getModule()->getSlug()));
	
		$params = $operation->getParameters();
		$responses = $operation->getResponses();
	
		switch ($type) {
			case Types::PAGINATE:
				$ok = $responses->get('200');
				$ok->setDescription(sprintf('Array of %s', $modelPluralName));
				$ok->getSchema()->setRef('#/definitions/' . 'Paged' . NameUtils::pluralize($modelObjectName));
				break;
	
			case Types::CREATE:
				// params
				$body = $params->getByName('body');
				$body->setName('body');
				$body->setIn('body');
				$body->setDescription(sprintf('The new %s', $modelName));
				$body->setRequired(true);
				$body->getSchema()->setRef('#/definitions/Writable' . $modelObjectName);
	
				// response
				$ok = $responses->get('201');
				$ok->setDescription(sprintf('%s created', $modelName));
				break;
	
			case Types::READ:
				// response
				$ok = $responses->get('200');
				$ok->setDescription(sprintf('gets the %s', $modelName));
				$ok->getSchema()->setRef('#/definitions/' . $modelObjectName);
				break;
	
			case Types::UPDATE:
				// response
				$ok = $responses->get('200');
				$ok->setDescription(sprintf('%s updated', $modelName));
				$ok->getSchema()->setRef('#/definitions/' . $modelObjectName);
				break;
	
			case Types::DELETE:
				// response
				$ok = $responses->get('204');
				$ok->setDescription(sprintf('%s deleted', $modelName));
				break;
		}
	
		if ($type == Types::READ || $type == Types::UPDATE || $type == Types::DELETE) {
			// params
			$id = $params->getByName('id');
			$id->setIn('path');
			$id->setDescription(sprintf('The %s id', $modelName));
			$id->setRequired(true);
			$id->setType('integer');
	
			// response
			$invalid = $responses->get('400');
			$invalid->setDescription('Invalid ID supplied');
			$invalid->getSchema()->setRef('#/definitions/Errors');
				
			$notfound = $responses->get('404');
			$notfound->setDescription(sprintf('No %s found', $modelName));
			$notfound->getSchema()->setRef('#/definitions/Errors');
		}
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

	protected function generateDefinitions(Swagger $swagger) {
		$definitions = $swagger->getDefinitions();
		
		// models
		$modelName = $this->io->getInput()->getOption('model');
		if ($modelName !== null) {
			$model = $this->modelService->getModel($modelName);
			$this->generateDefinition($definitions, $model);
		} else {
			foreach ($this->modelService->getModels() as $model) {
				$this->generateDefinition($definitions, $model);
			}
		}
		
		// general definitions
		$this->generateErrorDefinition($definitions);
		$this->generatePagedMeta($definitions);
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
	
	protected function generatePagedMeta(Definitions $definitions) {
		if ($this->needsPagedMeta) {
			$props = $definitions->get('PagedMeta')->setType('object')->getProperties();
			$names = ['total', 'first', 'next', 'previous', 'last'];
			
			foreach ($names as $name) {
				$props->get($name)->setType('integer');
			}
		}
	}
	
	protected function generateResourceIdentifier(Definitions $definitions) {
		if ($this->needsResourceIdentifier) {
			$props = $definitions->get('ResourceIdentifier')->setType('object')->getProperties();
			$this->generateIdentifier($props);
		}
	}
	
	protected function generateIdentifier(Definitions $props) {
		$props->get('id')->setType('string');
		$props->get('type')->setType('string');
	}
	
	protected function generateResourceData(Definitions $props) {
		$data = $props->get('data')->setType('object')->getProperties();
		$this->generateIdentifier($data);
		return $data;
	}

	protected function generateDefinition(Definitions $definitions, Table $model) {
		$this->logger->notice('Generating Definition for: ' . $model->getOriginCommonName());
		$modelObjectName = $model->getPhpName();
		$codegen = $this->codegenService->getCodegen();
		
		// stop if model is excluded
		if ($codegen->getExcludedApi()->contains($model->getOriginCommonName())) {
			return;
		}
		
		// paged model
		$this->needsPagedMeta = true;
		$pagedModel = 'Paged' . NameUtils::pluralize($modelObjectName);
		$paged = $definitions->get($pagedModel)->setType('object')->getProperties();
		$paged->get('data')
			->setType('array')
			->getItems()->setRef('#/definitions/' . $modelObjectName);
		$paged->get('meta')->setRef('#/definitions/PagedMeta');
		
		// writable model
		$writable = $definitions->get('Writable' . $modelObjectName)->setType('object')->getProperties();
		$this->generateModelProperties($writable, $model, true);

		// readable model
		$readable = $definitions->get($modelObjectName)->setType('object')->getProperties();
		$this->generateModelProperties($readable, $model, false);
	}
	
	protected function generateModelProperties(Definitions $props, Table $model, $write = false) {
		// links
		if (!$write) {
			$links = $props->get('links')->setType('object')->getProperties();
			$links->get('self')->setType('string');
		}
		
		// data
		$data = $this->generateResourceData($props);
		
		// attributes
		$attrs = $data->get('attributes');
		$attrs->setType('object');
		$this->generateModelAttributes($attrs->getProperties(), $model, $write);

		// relationships
		if ($this->hasRelationships($model)) {
			$relationships = $data->get('relationships')->setType('object')->getProperties();
			$this->generateModelRelationships($relationships, $model, $write);
		}
	}
	
	protected function generateModelAttributes(Definitions $props, Table $model, $write = false) {
		$modelName = $model->getOriginCommonName();
		$filter = $write 
			? $this->codegenService->getCodegen()->getWriteFilter($modelName)
			: $this->codegenService->getCodegen()->getReadFilter($modelName);

		if ($write) {
			$filter = array_merge($filter, $this->codegenService->getComputedFields($model));
		}
		
		// no id, already in identifier
		$filter[] = 'id';
		$types = ['int' => 'integer'];
		
		foreach ($model->getColumns() as $col) {
			$prop = $col->getName();
			
			if (!in_array($prop, $filter)) {
				$type = $col->getPhpType();
				if (isset($types[$type])) {
					$type = $types[$type];
				}
				$props->get($prop)->setType($type);
			}
		}

		return $props;
	}
	
	protected function hasRelationships(Table $model) {
		$relationships = $this->modelService->getRelationships($model);
		return $relationships->size() > 0;
	}
	
	protected function generateModelRelationships(Definitions $props, Table $model, $write = false) {
		$relationships = $this->modelService->getRelationships($model);
		
		foreach ($relationships->getAll() as $relationship) {
			// one-to-one
			if ($relationship->getType() == Relationship::ONE_TO_ONE) {
				$typeName = $relationship->getRelatedTypeName();
				$rel = $props->get($typeName)->setType('object')->getProperties();
				
				// links
				if (!$write) {
					$links = $rel->get('links')->setType('object')->getProperties();
					$links->get('self')->setType('string');
				}
				
				// data
				$this->generateResourceData($rel);
			}
		
			// ?-to-many
			else {
				$typeName = $relationship->getRelatedPluralTypeName();
				$rel = $props->get($typeName)->setType('object')->getProperties();
				
				// links
				if (!$write) {
					$links = $rel->get('links')->setType('object')->getProperties();
					$links->get('self')->setType('string');
				}
				
				// data
				$this->needsResourceIdentifier = true;
				$rel->get('data')
					->setType('array')
					->getItems()->setRef('#/definitions/ResourceIdentifier');
			}
		}
	}

}