<?php

namespace keeko\tools\command;

use keeko\tools\command\AbstractGenerateCommand;
use keeko\tools\utils\NameUtils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Database;

class GenerateApiCommand extends AbstractGenerateCommand {
	
	protected function configure() {
		$this
			->setName('generate:api')
			->setDescription('Generates the api for the module')
		;
	
		self::configureParameters($this);
	
		parent::configure();
	}
	
	public static function configureParameters(Command $command) {
		return GenerateResponseCommand::configureParameters($command);
	}
	
	public function getOptionKeys() {
		// get keys from dependent commands
		$command = $this->getApplication()->find('generate:response');
	
		return array_merge(parent::getOptionKeys(), $command->getOptionKeys());
	}
	
	public function getArgumentKeys() {
		// get keys from dependent commands
		$command = $this->getApplication()->find('generate:response');
		
		return array_merge(parent::getArgumentKeys(), $command->getArgumentKeys());
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->runCommand('generate:response', $input, $output);
	
		$package = $this->getPackage($input);
		$propel = $this->getPropelDatabase($input, $output);
		
		$module = $this->getKeekoModule($input);
		$api = isset($module['api']) ? $module['api'] : [];
		$api['swaggerVersion'] = '1.2';
		$api['resourcePath'] = '/' . $module['slug'];
		$apis = isset($api['apis']) ? $api['apis'] : [];
		
		// endpoints
		foreach ($this->getKeekoActions($input) as $name => $action) {
			$action['name'] = $name;
			$apis = $this->generateOperation($input, $output, $apis, $action);
		}
		
		// models
		$models = isset($api['models']) ? $api['models'] : [];
		
		// meta
		$meta = isset($models['Meta']) ? $models['Meta'] : [];
		$meta['id'] = 'Meta';
		$props = isset($meta['properties']) ? $meta['properties'] : [];
		$total = isset($props['total']) ? $props['total'] : [];
		$total['type'] = 'integer';
		$props['total'] = $total;
		
		$first = isset($props['first']) ? $props['first'] : [];
		$first['type'] = 'string';
		$props['first'] = $first;
		
		$next = isset($props['next']) ? $props['next'] : [];
		$next['type'] = 'string';
		$props['next'] = $next;
		
		$previous = isset($props['previous']) ? $props['previous'] : [];
		$previous['type'] = 'string';
		$props['previous'] = $previous;
		
		$last = isset($props['last']) ? $props['last'] : [];
		$last['type'] = 'string';
		$props['last'] = $last;
		
		$meta['properties'] = $props;
		$models['Meta'] = $meta;
		
		
		$model = $this->getModel($input, $propel);
		if ($model !== null) {
			$models = $this->generateModel($models, $module, $propel, $model);
		} else {
			foreach ($this->getPropelModels($input, $output) as $model) {
				$models = $this->generateModel($models, $module, $propel, $model->getName());
			}
		}

		// save api
		$api['apis'] = $apis;
		$api['models'] = $models;
		
		$package['extra']['keeko']['module']['api'] = $api;
		$this->saveComposer($package, $input, $output);
	}
	
	protected function generateModel($models, $module, Database $propel, $model) {
		$modelPlural = NameUtils::pluralize($model);
		$modelObject = $propel->getTable($model)->getPhpName();
		
		// Paged model
		$pagedModel = 'Paged' . NameUtils::pluralize($modelObject);
		$paged = isset($models[$pagedModel]) ? $models[$pagedModel] : [];
		$paged['id'] = $pagedModel;
		
		$props = isset($paged['properties']) ? $paged['properties'] : [];
		
		$pagedModels = isset($props[$modelPlural]) ? $props[$modelPlural] : [];
		$pagedModels['type'] = 'array';
		$items = isset($pagedModels['items']) ? $pagedModels['items'] : [];
		$items['$ref'] = $modelObject;
		$pagedModels['items'] = $items;
		$props[$modelPlural] = $pagedModels;
		
		$pagedMeta = isset($props['meta']) ? $props['meta'] : [];
		$pagedMeta['type'] = 'Meta';
		$props['meta'] = $pagedMeta;
		
		$paged['properties'] = $props;
		$models[$pagedModel] = $paged;
		
		
		$propelModel = $propel->getTable($model);
		
		// writable model
		$writableModelName = 'Writable' . $modelObject;
		$writableModel = isset($models[$writableModelName]) ? $models[$writableModelName] : [];
		$writableModel['id'] = $writableModelName;
		$writableProps = isset($writableModel['properties']) ? $writableModel['properties'] : [];
		$writableModel['properties'] = $this->generateModelProperties($propelModel, $writableProps, $module, true);
		
		$models[$writableModelName] = $writableModel;
		
		// readable model
		$readableModel = isset($models[$modelObject]) ? $models[$modelObject] : [];
		$readableModel['id'] = $modelObject;
		$readableProps = isset($readableModel['properties']) ? $readableModel['properties'] : [];
		$readableModel['properties'] = $this->generateModelProperties($propelModel, $readableProps, $module);
		
		$models[$modelObject] = $readableModel;
		
		return $models;
	}
	
	protected function generateModelProperties(Table $propel, $props, $module, $filterComputed = false) {
		$model = $propel->getOriginCommonName();
		$filter = $this->getFilter($module, $model, $filterComputed ? 'write' : 'read');
		if ($filterComputed) {
			$filter = $this->getComputedFields($propel);
		}
		foreach ($propel->getColumns() as $col) {
			$prop = $col->getName();
			
			if (!in_array($prop, $filter)) {
				$entry = isset($props[$prop]) ? $props[$prop] : [];
				$entry['type'] = $col->getPhpType();
				$props[$prop] = $entry;
			}
		}
		
		return $props;
	}
	
	protected function generateOperation(InputInterface $input, OutputInterface $output, $apis, $action) {
		$propel = $this->getPropelDatabase($input, $output);
		$model = $this->getModelFromName($action['name']);
		$modelPlural = NameUtils::pluralize($model);
		$modelObject = $propel->getTable($model)->getPhpName();
		$type = $this->getType($input, $action['name'], $model);
		
		// find path branch
		switch ($type) {
			case 'list':
			case 'create':
				$path = '/' . $modelPlural;
				break;
				
			case 'read':
			case 'update':
			case 'delete':
				$path = '/' . $modelPlural . '/{id}';
				break;
				
			default:
				throw new \RuntimeException('type (%s) not found, can\'t continue.');
				break;
		}

		list($branchIndex, $branch) = $this->findPathBranch($apis, $path);
		$branch['path'] = $path;
		$method = $this->getMethod($type);
		$operations = isset($branch['operations']) ? $branch['operations'] : [];
		list($operationIndex, $operation) = $this->findOperation($operations, $method);
		$operation['method'] = strtoupper($method);
		$operation['summary'] = $action['title'];
		$operation['nickname'] = $action['name'];
		$params = isset($operation['parameters']) ? $operation['parameters'] : [];
		$responseMessages = isset($operation['responseMessages']) ? $operation['responseMessages'] : [];
		
		switch ($type) {
			case 'list':
				$operation['type'] = 'Paged' . NameUtils::pluralize($modelObject);
				break;
				
			case 'create':
				$operation['type'] = $modelObject;
				
				// params
				list($paramIndex, $param) = $this->findParam($params, 'body');
				
				$param['name'] = 'body';
				$param['description'] = sprintf('The new %s', $model);
				$param['required'] = true;
				$param['type'] = 'Writable' . $modelObject;
				$param['paramType'] = 'body';
				
				$params = $this->updateArray($params, $paramIndex, $param);
				break;
				
			case 'read':
			case 'update':
			case 'delete':
				$operation['type'] = $modelObject;
				
				// params
				list($paramIndex, $param) = $this->findParam($params, 'id');
				
				$param['name'] = 'id';
				$param['description'] = sprintf('The %s id', $model);
				$param['required'] = true;
				$param['type'] = 'id';
				$param['paramType'] = 'path';
				
				$params = $this->updateArray($params, $paramIndex, $param);
				
				// response messages
				list($responseIndex, $response) = $this->findResponse($responseMessages, 400);
				$response['code'] = '400';
				$response['message'] = 'Invalid ID supplied'; 
				$responseMessages = $this->updateArray($responseMessages, $responseIndex, $response);
				
				list($responseIndex, $response) = $this->findResponse($responseMessages, 404);
				$response['code'] = '404';
				$response['message'] = sprintf('No %s found', $model);
				$responseMessages = $this->updateArray($responseMessages, $responseIndex, $response);
				break;
		}

		if (count($params) > 0) {
			$operation['parameters'] = $params;
		} else {
			unset($operation['parameters']);
		}
		
		if (count($responseMessages) > 0) {
			$operation['responseMessages'] = $responseMessages;
		} else {
			unset($operation['responseMessages']);
		}

		$operations = $this->updateArray($operations, $operationIndex, $operation);
		$branch['operations'] = $operations;
		$apis = $this->updateArray($apis, $branchIndex, $branch);
		
		return $apis;
	}
	
	private function getModelFromName($name) {
		return substr($name, 0, strpos($name, '-'));
	}
	
	private function updateArray($array, $index, $value) {
		if ($index === null) {
			$array[] = $value;
		} else {
			$array[$index] = $value;
		}
		
		return $array;
	}
	
	private function getMethod($type) {
		$methods = [
			'list' => 'GET',
			'create' => 'POST',
			'read' => 'GET',
			'update' => 'PATCH',
			'delete' => 'DELETE'
		];
		
		return $methods[$type];
	}
	
	private function findPathBranch($apis, $path) {
		foreach ($apis as $index => $branch) {
			if (isset($branch['path']) && $branch['path'] === $path) {
				return [$index, $branch];
			}
		}
		return [null, []];
	}
	
	private function findOperation($operations, $method) {
		foreach ($operations as $index => $operation) {
			if (isset($operation['method']) && strtoupper($operation['method']) === strtoupper($method)) {
				return [$index, $operation];
			}
		}
		return [null, []];
	}
	
	private function findParam($params, $name) {
		foreach ($params as $index => $param) {
			if (isset($param['name']) && $param['name'] === $name) {
				return [$index, $param];
			}
		}
		return [null, []];
	}
	
	private function findResponse($responses, $code) {
		foreach ($responses as $index => $response) {
			if (isset($response['code']) && $response['code'] == $code) {
				return [$index, $response];
			}
		}
		return [null, []];
	}
}