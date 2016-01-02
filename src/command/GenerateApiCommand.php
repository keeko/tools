<?php
namespace keeko\tools\command;

use gossi\swagger\collections\Definitions;
use gossi\swagger\collections\Paths;
use gossi\swagger\Swagger;
use keeko\tools\command\AbstractGenerateCommand;
use keeko\tools\utils\NameUtils;
use phootwork\file\File;
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
	
	protected function generatePaths(Swagger $swagger) {
		$paths = $swagger->getPaths();
		
		foreach ($this->packageService->getModule()->getActionNames() as $name) {
			$this->generateOperation($paths, $name);
		}
	}
	
	protected function generateOperation(Paths $paths, $actionName) {
		$this->logger->notice('Generating Operation for: ' . $actionName);
	
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
				throw new \RuntimeException('type (%s) not found, can\'t continue.');
				break;
		}
	
	
		$path = $paths->get($endpoint);
		$method = $this->getMethod($type);
		$operation = $path->getOperation($method);
		$operation->setDescription($action->getTitle());
		$operation->setOperationId($action->getName());
		$operation->getProduces()->add('application/json');
	
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
			'update' => 'PUT',
			'delete' => 'DELETE'
		];
	
		return $methods[$type];
	}

	protected function generateDefinitions(Swagger $swagger) {
		$definitions = $swagger->getDefinitions();
		
		// meta
		$this->generateMeta($definitions);

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
	
	protected function generateMeta(Definitions $definitions) {
		$meta = $definitions->get('Meta');
		$props = $meta->getProperties();
		$names = ['total', 'first', 'next', 'previous', 'last'];
		
		foreach ($names as $name) {
			$props->get($name)->setType('integer');
		}
	}

	protected function generateDefinition(Definitions $definitions, $modelName) {
		$this->logger->notice('Generating Definition for: ' . $modelName);
		$model = $this->modelService->getModel($modelName);
		$modelObjectName = $model->getPhpName();
		$modelPluralName = NameUtils::pluralize($model->getOriginCommonName());
		
		// paged model
		$pagedModel = 'Paged' . NameUtils::pluralize($modelObjectName);
		$paged = $definitions->get($pagedModel)->getProperties();
		$paged->get($modelPluralName)
			->setType('array')
			->getItems()->setRef('#/definitions/' . $modelObjectName);
		$paged->get('meta')->setRef('#/definitions/Meta');
		
		// writable model
		$writable = $definitions->get('Writable' . $modelObjectName)->getProperties();
		$this->generateModelProperties($writable, $model, true);

		// readable model
		$readable = $definitions->get($modelObjectName)->getProperties();
		$this->generateModelProperties($readable, $model, false);
	}
	
	protected function generateModelProperties(Definitions $props, Table $model, $write = false) {
		$modelName = $model->getOriginCommonName();
		$filter = $write 
			? $this->codegenService->getCodegen()->getWriteFilter($modelName)
			: $this->codegenService->getCodegen()->getReadFilter($modelName);
		
		if ($write) {
			$filter = array_merge($filter, $this->codegenService->getComputedFields($model));
		}
		
		foreach ($model->getColumns() as $col) {
			$prop = $col->getName();
			
			if (!in_array($prop, $filter)) {
				$props->get($prop)->setType($col->getPhpType());
			}
		}

		return $props;
	}

}