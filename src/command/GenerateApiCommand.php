<?php
namespace keeko\tools\command;

use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\swagger\collections\Definitions;
use gossi\swagger\collections\Paths;
use gossi\swagger\Swagger;
use gossi\swagger\Tag;
use keeko\framework\utils\NameUtils;
use phootwork\file\File;
use phootwork\lang\Text;
use Propel\Generator\Model\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateApiCommand extends AbstractKeekoCommand {

	protected function configure() {
		$this
			->setName('generate:api')
			->setDescription('Generates the api for the module')
		;
		
		$this->configureGenerateOptions();
			
		parent::configure();
	}
	
	/**
	 * Checks whether api can be generated at all by reading composer.json and verify
	 * all required information are available
	 */
	private function preCheck() {
		$module = $this->packageService->getModule();
		if ($module === null) {
			throw new \DomainException('No module definition found in composer.json - please run `keeko init`.');
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->preCheck();
		
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
	
// 	/**
// 	 * Adds the APIModelInterface to package models
// 	 * 
// 	 */
// 	protected function prepareModels() {
// 		$models = $this->modelService->getPackageModelNames();
		
// 		foreach ($models as $modelName) {
// 			$tableName = $this->modelService->getTableName($modelName);
// 			$model = $this->modelService->getModel($tableName);
// 			$class = new PhpClass(str_replace('\\\\', '\\', $model->getNamespace() . '\\' . $model->getPhpName()));
// 			$file = new File($this->codegenService->getFilename($class));
			
// 			if ($file->exists()) {
// 				$class = PhpClass::fromFile($this->codegenService->getFilename($class));
// 				if (!$class->hasInterface('APIModelInterface')) {
// 					$class->addUseStatement('keeko\\core\\model\\types\\APIModelInterface');
// 					$class->addInterface('APIModelInterface');
// // 					$typeName =  $this->package->getCanonicalName() . '.' . NameUtils::dasherize($modelName);
// // 					$class->setMethod(PhpMethod::create('getAPIType')
// // 						->setBody('return \''.$typeName . '\';')
// // 					);
	
// 					$this->codegenService->dumpStruct($class, true);
// 				}
// 			}
// 		}
// 	}

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
		$prefix = substr($actionName, 0, strrpos($actionName, 'relationship') + 12);
		$module = $this->packageService->getModule();

		// test for to-many relationship:
		$many = $module->hasAction($prefix . '-read') 
			&& $module->hasAction($prefix . '-update')
			&& $module->hasAction($prefix . '-add')
			&& $module->hasAction($prefix . '-remove')
		;
		$single = $module->hasAction($prefix . '-read') 
			&& $module->hasAction($prefix . '-update')
			&& !$many
		;
		
		if (!$many && !$single) {
			$this->io->writeln(sprintf('<comment>Couldn\'t detect whether %s is a to-one or to-many relationship, skin generating endpoints</comment>', $actionName));
			return;
		}
		
		// find model names
		$modelName = substr($actionName, 0, strpos($actionName, 'to') - 1);
		$start = strpos($actionName, 'to') + 3;
		$foreignModelName = substr($actionName, $start, strpos($actionName, 'relationship') - 1 - $start);
		
		// stop, if one of the models is excluded from api
		$codegen = $this->codegenService->getCodegen();
		$excluded = $codegen->getExcludedModels();
		if ($excluded->contains($modelName) || $excluded->contains($foreignModelName)) {
			return;
		}
		
		$action = $this->packageService->getAction($actionName);
		$type = substr($actionName, strrpos($actionName, '-') + 1);
		$method = $this->getMethod($type);
		$endpoint = '/' . NameUtils::pluralize($modelName) . '/{id}/relationship/' . ($single ?
			$foreignModelName : NameUtils::pluralize($foreignModelName));
		
		$path = $paths->get($endpoint);
		$method = $this->getMethod($type);
		$operation = $path->getOperation($method);
		$operation->setDescription($action->getTitle());
		$operation->setOperationId($action->getName());
		$operation->getTags()->clear();
		$operation->getTags()->add(new Tag($this->package->getKeeko()->getModule()->getSlug()));
		
		$params = $operation->getParameters();
		$responses = $operation->getResponses();
		
		// general model related params
		// params
		$id = $params->getByName('id');
		$id->setIn('path');
		$id->setDescription(sprintf('The %s id', $modelName));
		$id->setRequired(true);
		$id->setType('integer');
		
		// response
		$invalid = $responses->get('400');
		$invalid->setDescription('Invalid ID supplied');
		
		$notfound = $responses->get('404');
		$notfound->setDescription(sprintf('No %s found', $modelName));
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
		
		if ($codegen->getExcludedModels()->contains($modelName)) {
			return;
		}
	
		$type = $this->packageService->getActionType($actionName, $modelName);
		$modelObjectName = $database->getTable($tableName)->getPhpName();
		$modelPluralName = NameUtils::pluralize($modelName);
	
		// find path branch
		switch ($type) {
			case 'list':
			case 'create':
				$endpoint = '/' . $modelPluralName;
				break;
	
			case 'read':
			case 'update':
			case 'delete':
				$endpoint = '/' . $modelPluralName . '/{id}';
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
		$operation->getTags()->clear();
		$operation->getTags()->add(new Tag($this->package->getKeeko()->getModule()->getSlug()));
	
		$params = $operation->getParameters();
		$responses = $operation->getResponses();
	
		switch ($type) {
			case 'list':
				$ok = $responses->get('200');
				$ok->setDescription(sprintf('Array of %s', $modelPluralName));
				$ok->getSchema()->setRef('#/definitions/' . 'Paged' . NameUtils::pluralize($modelObjectName));
				break;
	
			case 'create':
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
	
			case 'read':
				// response
				$ok = $responses->get('200');
				$ok->setDescription(sprintf('gets the %s', $modelName));
				$ok->getSchema()->setRef('#/definitions/' . $modelObjectName);
				break;
	
			case 'update':
				// response
				$ok = $responses->get('200');
				$ok->setDescription(sprintf('%s updated', $modelName));
				$ok->getSchema()->setRef('#/definitions/' . $modelObjectName);
				break;
	
			case 'delete':
				// response
				$ok = $responses->get('204');
				$ok->setDescription(sprintf('%s deleted', $modelName));
				break;
		}
	
		if ($type == 'read' || $type == 'update' || $type == 'delete') {
			// params
			$id = $params->getByName('id');
			$id->setIn('path');
			$id->setDescription(sprintf('The %s id', $modelName));
			$id->setRequired(true);
			$id->setType('integer');
	
			// response
			$invalid = $responses->get('400');
			$invalid->setDescription('Invalid ID supplied');
				
			$notfound = $responses->get('404');
			$notfound->setDescription(sprintf('No %s found', $modelName));
		}
	
		// response - @TODO Error model
	}
	
	private function getMethod($type) {
		$methods = [
			'list' => 'get',
			'create' => 'post',
			'read' => 'get',
			'update' => 'patch',
			'delete' => 'delete',
			'add' => 'post',
			'remove' => 'delete'
		];
	
		return $methods[$type];
	}

	protected function generateDefinitions(Swagger $swagger) {
		$definitions = $swagger->getDefinitions();
		
		// general definitions
		$this->generatePagedMeta($definitions);
		$this->generateResourceIdentifier($definitions); 

		// models
		$modelName = $this->modelService->getModelName();
		if ($modelName !== null) {
			$this->generateDefinition($definitions, $modelName);
		} else {
			foreach ($this->modelService->getModels() as $model) {
				$this->generateDefinition($definitions, $model);
			}
		}
	}
	
	protected function generatePagedMeta(Definitions $definitions) {
		$props = $definitions->get('PagedMeta')->setType('object')->getProperties();
		$names = ['total', 'first', 'next', 'previous', 'last'];
		
		foreach ($names as $name) {
			$props->get($name)->setType('integer');
		}
	}
	
	protected function generateResourceIdentifier(Definitions $definitions) {
		$props = $definitions->get('ResourceIdentifier')->setType('object')->getProperties();
		$this->generateIdentifier($props);
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
		if ($codegen->getExcludedModels()->contains($model->getOriginCommonName())) {
			return;
		}
		
		// paged model
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
		return (count($model->getForeignKeys()) + count($model->getCrossFks())) > 0;
	}
	
	protected function generateModelRelationships(Definitions $props, Table $model, $write = false) {
		$relationships = $this->modelService->getRelationships($model);
		
		// to-one
		foreach ($relationships->getOne() as $one) {
			$typeName = $one->getRelatedTypeName();
			$rel = $props->get($typeName)->setType('object')->getProperties();
			
			// links
			if (!$write) {
				$links = $rel->get('links')->setType('object')->getProperties();
				$links->get('self')->setType('string');
			}
			
			// data
			$this->generateResourceData($rel);
		}
		
		// to-many
		foreach ($relationships->getMany() as $many) {
			$typeName = $many->getRelatedTypeName();
			$rel = $props->get($typeName)->setType('object')->getProperties();
			
			// links
			if (!$write) {
				$links = $rel->get('links')->setType('object')->getProperties();
				$links->get('self')->setType('string');
			}
			
			// data
			$rel->get('data')
				->setType('array')
				->getItems()->setRef('#/definitions/ResourceIdentifier');
		}
	}

}