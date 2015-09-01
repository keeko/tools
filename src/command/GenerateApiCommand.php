<?php
namespace keeko\tools\command;

use keeko\tools\command\AbstractGenerateCommand;
use keeko\tools\utils\NameUtils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Database;
use keeko\tools\helpers\PackageServiceTrait;
use keeko\tools\helpers\ModelServiceTrait;
use keeko\tools\helpers\CodeGeneratorServiceTrait;
use phootwork\json\Json;
use phootwork\json\JsonException;
use keeko\tools\exceptions\JsonEmptyException;
use Symfony\Component\Filesystem\Filesystem;

class GenerateApiCommand extends AbstractGenerateCommand {

	use ModelServiceTrait;
	use CodeGeneratorServiceTrait;
	
	protected function configure() {
		$this
			->setName('generate:api')
			->setDescription('Generates the api for the module')
		;

		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$module = $this->getModule();
		$api = $this->service->getJsonService()->read($this->getApiFile());
		$api['swagger'] = '2.0';
		$api['basePath'] = '/' . $module['slug'];
		$api['paths'] = $this->generatePaths($api);
		$api['definitions'] = $this->generateDefinitions($api);
		
		$this->service->getJsonService()->read($this->getApiFile(), $api);
	}
	
	protected function generatePaths($api) {
		$paths = isset($api['paths']) ? $api['paths'] : [];
		foreach ($this->getActions() as $name => $action) {
			$action['name'] = $name;
			$paths = $this->generateOperation($paths, $action);
		}
		return $paths;
	}
	
	protected function generateDefinitions($api) {
		$definitions = isset($api['definitions']) ? $api['definitions'] : [];
		
		// meta
		$definitions['Meta'] = $this->generateMeta($definitions);
		
		// models
		$modelName = $this->getModelName();
		if ($modelName !== null) {
			$definitions = $this->generateDefinition($definitions, $modelName);
		} else {
			foreach ($this->getModels() as $modelName) {
				$definitions = $this->generateDefinition($definitions, $modelName->getName());
			}
		}
		
		return $definitions;
	}
	
	protected function generateMeta($definitions) {
		$meta = isset($definitions['Meta']) ? $definitions['Meta'] : [];
		$props = isset($meta['properties']) ? $meta['properties'] : [];
		$props = $this->generateProp($props, 'total');
		$props = $this->generateProp($props, 'first');
		$props = $this->generateProp($props, 'next');
		$props = $this->generateProp($props, 'previous');
		$props = $this->generateProp($props, 'last');
		
		$meta['properties'] = $props;
		return $meta;
	}
	
	protected function generateProp($props, $name) {
		$prop = isset($props[$name]) ? $props[$name] : [];
		$prop['type'] = 'integer';
		$props[$name] = $prop;
		
		return $props;
	}

	protected function generateDefinition($definitions, $modelName) {
		$this->logger->notice('Generating Definition for: ' . $modelName);
		$model = $this->getModel($modelName);
		$modelObjectName = $model->getPhpName();
		$modelPluralName = NameUtils::pluralize($model->getOriginCommonName());
		
		// paged model
		$pagedModel = 'Paged' . NameUtils::pluralize($modelObjectName);
		$definitions[$pagedModel] = [
			'properties' => [
				$modelPluralName => [
					'type' => 'array',
					'items' => [
						'$ref' => '#/definitions/' . $modelObjectName
					]
				],
				'meta' => [
					'$ref' => '#/definitions/Meta'
				]
			] 
		];
		
		// writable model
		$definitions['Writable' . $modelObjectName] = [
			'properties' => $this->generateModelProperties($model, true)
		];
		
		// readable model
		$definitions[$modelObjectName] = [
			'properties' => $this->generateModelProperties($model)
		];
		
		return $definitions;
	}
	
	protected function generateModelProperties(Table $propel, $write = false) {
		$props = [];
		$modelName = $propel->getOriginCommonName();
		$filter = $this->getFilter($modelName, $write ? 'write' : 'read');
		if ($write) {
			$filter = array_merge($filter, $this->getComputedFields($propel));
		}
		foreach ($propel->getColumns() as $col) {
			$prop = $col->getName();
			
			if (!in_array($prop, $filter)) {
				$props[$prop] = [
					'type' => $col->getPhpType()
				];
			}
		}

		return $props;
	}
	
	protected function generateOperation($paths, $action) {
		$this->logger->notice('Generating Operation for: ' . $action['name']);
		
		$database = $this->getDatabase();
		$modelName = $this->getModelNameFromNameAction($action['name']);
		$tableName = $database->getTablePrefix() . $modelName;
		
		if (!$database->hasTable($tableName)) {
			return $paths;
		}
		
		$modelObject = $database->getTable($tableName)->getPhpName();
		$modelPlural = NameUtils::pluralize($modelName);
		
		$type = $this->getActionType($action['name'], $modelName);
		
		// find path branch
		switch ($type) {
			case 'list':
			case 'create':
				$endpoint = '/' . $modelPlural;
				break;

			case 'read':
			case 'update':
			case 'delete':
				$endpoint = '/' . $modelPlural . '/{id}';
				break;

			default:
				throw new \RuntimeException('type (%s) not found, can\'t continue.');
				break;
		}

		$path = isset($paths[$endpoint]) ? $paths[$endpoint] : [];
		$method = $this->getMethod($type);
		$operation = isset($path[$method]) ? $path[$method] : [];

		$operation['description'] = $action['title'];
		$operation['operationId'] = $action['name'];
		$operation['produces'] = ['application/json'];
		$params = isset($operation['parameters']) ? $operation['parameters'] : [];
		$responses = isset($operation['responses']) ? $operation['responses'] : [];
		
		switch ($type) {
			case 'list':
				$ok = isset($responses['200']) ? $responses['200'] : [];
				$ok['description'] = sprintf('Array of %s', $modelPlural);
				$ok['schema'] = ['$ref' => '#/definitions/' . 'Paged' . NameUtils::pluralize($modelObject)];
				
				$responses['200'] = $ok;
				break;
				
			case 'create':
				// params
				list($paramIndex, $param) = $this->findParam($params, 'body');
				
				$param['name'] = 'body';
				$param['in'] = 'body';
				$param['description'] = sprintf('The new %s', $modelName);
				$param['required'] = true;
				$param['schema'] = ['$ref' => '#/definitions/Writable' . $modelObject];
				
				$params = $this->updateArray($params, $paramIndex, $param);
				
				// response
				$ok = isset($responses['201']) ? $responses['201'] : [];
				$ok['description'] = sprintf('%s created', $modelName);
				
				$responses['201'] = $ok;
				break;
				
			case 'read':
				// response
				$ok = isset($responses['200']) ? $responses['200'] : [];
				$ok['description'] = sprintf('gets the %s', $modelName);
				$ok['schema'] = ['$ref' => '#/definitions/' . $modelObject];
				
				$responses['200'] = $ok;
				break;
				
			case 'update':
				// response
				$ok = isset($responses['200']) ? $responses['200'] : [];
				$ok['description'] = sprintf('%s updated', $modelName);
				$ok['schema'] = ['$ref' => '#/definitions/' . $modelObject];
				
				$responses['200'] = $ok;
				break;
				
			case 'delete':
				// response
				$ok = isset($responses['204']) ? $responses['204'] : [];
				$ok['description'] = sprintf('%s deleted', $modelName);
				
				$responses['204'] = $ok;
				break;
		}
		
		if ($type == 'read' || $type == 'update' || $type == 'delete') {
			// params
			list($paramIndex, $param) = $this->findParam($params, 'id');
			
			$param['name'] = 'id';
			$param['in'] = 'path';
			$param['description'] = sprintf('The %s id', $modelName);
			$param['required'] = true;
			$param['type'] = 'integer';
			
			$params = $this->updateArray($params, $paramIndex, $param);

			// response
			$invalid = isset($responses['400']) ? $responses['400'] : [];
			$invalid['description'] = 'Invalid ID supplied';
			$responses['400'] = $invalid;
			
			$notfound = isset($responses['404']) ? $responses['404'] : [];
			$notfound['description'] = sprintf('No %s found', $modelName);
			$responses['404'] = $notfound;
		}
		
		// response - @TODO Error model
		

		if (count($params) > 0) {
			$operation['parameters'] = $params;
		} else {
			unset($operation['parameters']);
		}
		
		if (count($responses) > 0) {
			$operation['responses'] = $responses;
		} else {
			unset($operation['responses']);
		}

		$path[$method] = $operation;
		$paths[$endpoint] = $path;

		return $paths;
	}
	
	private function getModelNameFromNameAction($name) {
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
			'update' => 'PUT',
			'delete' => 'DELETE'
		];
		
		return $methods[$type];
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