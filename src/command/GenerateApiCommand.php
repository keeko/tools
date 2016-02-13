<?php
namespace keeko\tools\command;

use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\swagger\collections\Definitions;
use gossi\swagger\collections\Paths;
use gossi\swagger\Swagger;
use keeko\core\schema\ActionSchema;
use keeko\tools\command\AbstractGenerateCommand;
use keeko\tools\generator\action\ToManyRelationshipAddActionGenerator;
use keeko\tools\generator\action\ToManyRelationshipReadActionGenerator;
use keeko\tools\generator\action\ToManyRelationshipRemoveActionGenerator;
use keeko\tools\generator\action\ToManyRelationshipUpdateActionGenerator;
use keeko\tools\generator\action\ToOneRelationshipReadActionGenerator;
use keeko\tools\generator\action\ToOneRelationshipUpdateActionGenerator;
use keeko\tools\generator\response\ToManyRelationshipJsonResponseGenerator;
use keeko\tools\generator\response\ToOneRelationshipJsonResponseGenerator;
use keeko\tools\utils\NameUtils;
use phootwork\file\File;
use phootwork\lang\Text;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateApiCommand extends AbstractGenerateCommand {

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
		
		// prepare models ?
		$this->prepareModels();
		
		// generate relationship actions
		$this->generateRelationshipActions();
		
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

		$swagger->setVersion('2.0');
		$this->generatePaths($swagger);
		$this->generateDefinitions($swagger);
		
		$this->jsonService->write($api->getPathname(), $swagger->toArray());
		$this->io->writeln(sprintf('API for <info>%s</info> written at <info>%s</info>', $this->package->getFullName(), $api->getPathname()));
	}
	
	/**
	 * Adds the APIModelInterface to package models
	 * 
	 */
	protected function prepareModels() {
		$models = $this->modelService->getPackageModelNames();
		
		foreach ($models as $modelName) {
			$tableName = $this->modelService->getTableName($modelName);
			$model = $this->modelService->getModel($tableName);
			$class = new PhpClass(str_replace('\\\\', '\\', $model->getNamespace() . '\\' . $model->getPhpName()));
			$file = new File($this->codegenService->getFilename($class));
			
			if ($file->exists()) {
				$class = PhpClass::fromFile($this->codegenService->getFilename($class));
				if (!$class->hasInterface('APIModelInterface')) {
					$class->addUseStatement('keeko\\core\\model\\types\\APIModelInterface');
					$class->addInterface('APIModelInterface');
// 					$typeName =  $this->package->getCanonicalName() . '.' . NameUtils::dasherize($modelName);
// 					$class->setMethod(PhpMethod::create('getAPIType')
// 						->setBody('return \''.$typeName . '\';')
// 					);
	
					$this->codegenService->dumpStruct($class, true);
				}
			}
		}
	}

	protected function generateRelationshipActions() {
		$models = $this->modelService->getPackageModelNames();
		
		foreach ($models as $modelName) {
			$model = $this->modelService->getModel($modelName);
			$relationships = $this->getRelationships($model);
			
			// to-one relationships
			foreach ($relationships['one'] as $one) {
				$fk = $one['fk'];
				$this->generateToOneRelationshipAction($model, $fk->getForeignTable(), $fk);
			}
		
			// to-many relationships
			foreach ($relationships['many'] as $many) {
				$fk = $many['fk'];
				$cfk = $many['cfk'];
				$this->generateToManyRelationshipAction($model, $fk->getForeignTable(), $cfk->getMiddleTable());
			}
		}
	}
	
	protected function getRelationships(Table $model) {
		// to-one relationships
		$one = [];
		$fks = $model->getForeignKeys();
		foreach ($fks as $fk) {
			$one[] = [
				'fk' => $fk
			];
		}
		
		// to-many relationships
		$many = [];
		$cfks = $model->getCrossFks();
		foreach ($cfks as $cfk) {
			foreach ($cfk->getMiddleTable()->getForeignKeys() as $fk) {
				if ($fk->getForeignTable() != $model) {
					$many[] = [
						'fk' => $fk,
						'cfk' => $cfk
					];
					break;
				}
			}
		}
		
		return [
			'one' => $one,
			'many' => $many
		];
	}

	protected function generateToOneRelationshipAction(Table $model, Table $foreign, ForeignKey $fk) {
		$module = $this->package->getKeeko()->getModule();
		$fkModelName = $foreign->getPhpName();
		$actionNamePrefix = sprintf('%s-to-%s-relationship',
			NameUtils::dasherize($model->getPhpName()),
			NameUtils::dasherize($fkModelName));
		$actions = [];
		
		// response class name
		$response = sprintf('%s\\response\\%s%sJsonResponse',
			$this->modelService->getRootNamespace(),
			$model->getPhpName(),
			$fkModelName
		);
		
		$generators = [
			'read' => new ToOneRelationshipReadActionGenerator($this->service),
			'update' => new ToOneRelationshipUpdateActionGenerator($this->service)
		];
		$titles = [
			'read' => 'Reads the relationship of {model} to {foreign}',
			'update' => 'Updates the relationship of {model} to {foreign}'
		];
		
		foreach (array_keys($generators) as $type) {
			// generate fqcn
			$className = sprintf('%s%s%sAction', $model->getPhpName(), $fkModelName, ucfirst($type));
			$fqcn = $this->modelService->getRootNamespace() . '\\action\\' . $className;
				
			// generate action
			$action = new ActionSchema($actionNamePrefix . '-' . $type);
			$action->addAcl('admin');
			$action->setClass($fqcn);
			$action->setTitle(str_replace(
				['{model}', '{foreign}'],
				[$model->getOriginCommonName(), $foreign->getoriginCommonName()],
				$titles[$type])
			);
			$action->setResponse('json', $response);
			$module->addAction($action);
			$actions[$type] = $action;
				
			// generate class
			$generator = $generators[$type];
			$class = $generator->generate(new PhpClass($fqcn), $model, $foreign);
			$this->codegenService->dumpStruct($class, true);
		}
		
		// generate response class
		$generator = new ToOneRelationshipJsonResponseGenerator($this->service, $model, $foreign);
		$response = $generator->generate($actions['read']);
		$this->codegenService->dumpStruct($response, true);

// 		// generate read action
// 		$className = sprintf('%s%sReadAction', $model->getPhpName(), $fkModelName);
// 		$fqcn = $this->modelService->getRootNamespace() . '\\action\\' . $className;
// 		$responseFqcn = str_replace(['action', 'Action'], ['response', 'JsonResponse'], $fqcn);
		
// 		$readAction = new ActionSchema($actionNamePrefix . '-read');
// 		$readAction->addAcl('admin');
// 		$readAction->setClass($fqcn);
// 		$readAction->setTitle(sprintf('Reads the relationship of %s to %s', 
// 			$model->getCommonName(), $foreign->getCommonName())
// 		);
// 		$readAction->setResponse('json', $responseFqcn);
// 		$module->addAction($readAction);
		
// 		// generate read response
// 		$generator = new ToOneRelationshipJsonResponseGenerator($this->service, $foreign);
// 		$response = $generator->generate($readAction);
// 		$this->codegenService->dumpStruct($response, true);
		
// 		// generate read class
// 		$class = new PhpClass($fqcn);
// 		$generator = new ToOneRelationshipReadActionGenerator($this->service);
// 		$class = $generator->generate($class, $model);
// 		$this->codegenService->dumpStruct($class, true);
		
// 		// generate update action
// 		$className = sprintf('%s%sUpdateAction', $model->getPhpName(), $fkModelName);
// 		$fqcn = $this->modelService->getRootNamespace() . '\\action\\' . $className;
// 		$updateAction = new ActionSchema($actionNamePrefix . '-update');
// 		$updateAction->addAcl('admin');
// 		$updateAction->setClass($fqcn);
// 		$updateAction->setTitle(sprintf('Updates the relationship of %s to %s',
// 			$model->getCommonName(), $foreign->getCommonName())
// 		);
// 		$updateAction->setResponse('json', $responseFqcn);
// 		$module->addAction($updateAction);
		
// 		// generate update class
// 		$class = new PhpClass($fqcn);
// 		$generator = new ToOneRelationshipUpdateActionGenerator($this->service);
// 		$class = $generator->generate($class, $model, $foreign, $fk);
// 		$this->codegenService->dumpStruct($class, true);
	}
	
	protected function generateToManyRelationshipAction(Table $model, Table $foreign, Table $middle) {
		$module = $this->package->getKeeko()->getModule();
		$fkModelName = $foreign->getPhpName();
		$actionNamePrefix = sprintf('%s-to-%s-relationship',
			NameUtils::dasherize($model->getPhpName()),
			NameUtils::dasherize($fkModelName));
		$actions = [];
		
		// response class name
		$response = sprintf('%s\\response\\%s%sJsonResponse', 
			$this->modelService->getRootNamespace(),
			$model->getPhpName(),
			$fkModelName
		);

		$generators = [
			'read' => new ToManyRelationshipReadActionGenerator($this->service), 
			'update' => new ToManyRelationshipUpdateActionGenerator($this->service), 
			'add' => new ToManyRelationshipAddActionGenerator($this->service),
			'remove' => new ToManyRelationshipRemoveActionGenerator($this->service)
		];
		$titles = [
			'read' => 'Reads the relationship of {model} to {foreign}',
			'update' => 'Updates the relationship of {model} to {foreign}',
			'add' => 'Adds {foreign} as relationship to {model}',
			'remove' => 'Removes {foreign} as relationship of {model}'
		];
		
		foreach (array_keys($generators) as $type) {
			// generate fqcn
			$className = sprintf('%s%s%sAction', $model->getPhpName(), $fkModelName, ucfirst($type));
			$fqcn = $this->modelService->getRootNamespace() . '\\action\\' . $className;
			
			// generate action
			$action = new ActionSchema($actionNamePrefix . '-' . $type);
			$action->addAcl('admin');
			$action->setClass($fqcn);
			$action->setTitle(str_replace(
				['{model}', '{foreign}'],
				[$model->getOriginCommonName(), $foreign->getoriginCommonName()], 
				$titles[$type])
			);
			$action->setResponse('json', $response);
			$module->addAction($action);
			$actions[$type] = $action;
			
			// generate class
			$generator = $generators[$type];
			$class = $generator->generate(new PhpClass($fqcn), $model, $foreign, $middle);
			$this->codegenService->dumpStruct($class, true);
		}
		
		// generate response class
		$generator = new ToManyRelationshipJsonResponseGenerator($this->service, $model, $foreign);
		$response = $generator->generate($actions['read']);
		$this->codegenService->dumpStruct($response, true);
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

		// find relationship objects
// 		$model = $this->modelService->getModel(NameUtils::toSnakeCase($modelName));
// 		$foreignModel = $this->modelService->getModel(NameUtils::toSnakeCase($foreignModelName));
// 		$fk = null;
// 		foreach ($model->getForeignKeys() as $key) {
// 			if ($key->getForeignTable() == $foreignModel) {
// 				$fk = $key;
// 			}
// 		}
// 		$fkName = $fk->getLocalColumn()->getName();
		
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
	
		if (!$database->hasTable($tableName)) {
			return $paths;
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
			'list' => 'GET',
			'create' => 'POST',
			'read' => 'GET',
			'update' => 'PATCH',
			'delete' => 'DELETE',
			'add' => 'POST',
			'remove' => 'DELETE'
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
			$definitions = $this->generateDefinition($definitions, $modelName);
		} else {
			foreach ($this->modelService->getModels() as $model) {
				$definitions = $this->generateDefinition($definitions, $model->getName());
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

	protected function generateDefinition(Definitions $definitions, $modelName) {
		$this->logger->notice('Generating Definition for: ' . $modelName);
		$model = $this->modelService->getModel($modelName);
		$modelObjectName = $model->getPhpName();
		
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
		
		foreach ($model->getColumns() as $col) {
			$prop = $col->getName();
			
			if (!in_array($prop, $filter)) {
				$props->get($prop)->setType($col->getPhpType());
			}
		}

		return $props;
	}
	
	protected function hasRelationships(Table $model) {
		return (count($model->getForeignKeys()) + count($model->getCrossFks())) > 0;
	}
	
	protected function generateModelRelationships(Definitions $props, Table $model, $write = false) {
		$relationships = $this->getRelationships($model);
		
		// to-one
		foreach ($relationships['one'] as $one) {
			$fk = $one['fk'];
			$typeName = NameUtils::dasherize($fk->getForeignTable()->getOriginCommonName());
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
		foreach ($relationships['many'] as $many) {
			$fk = $many['fk'];
			$foreignModel = $fk->getForeignTable();
			$typeName = NameUtils::pluralize(NameUtils::dasherize($foreignModel->getOriginCommonName()));
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