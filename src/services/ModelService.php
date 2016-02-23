<?php
namespace keeko\tools\services;

use keeko\framework\schema\ActionSchema;
use keeko\framework\schema\PackageSchema;
use keeko\tools\utils\NamespaceResolver;
use phootwork\collection\ArrayList;
use phootwork\lang\Text;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Table;
use Propel\Generator\Util\QuickBuilder;

class ModelService extends AbstractService {

	private $models = null;
	private $schema = null;
	private $namespace = null;
	
	/** @var Database */
	private $database = null;

	/**
	 * Returns the propel schema. The three locations, where the schema is looked up in:
	 *
	 * 1. --schema option (if available)
	 * 2. database/schema.xml
	 * 3. core/database/schema.xml
	 *
	 * @throws \RuntimeException
	 * @return string the path to the schema
	 */
	public function getSchema() {
		$input = $this->io->getInput();
		if ($this->schema === null) {
			$workDir = $this->service->getProject()->getRootPath();
			$schema = null;
			$schemas = [
				$input->hasOption('schema') ? $input->getOption('schema') : '',
				$workDir . '/database/schema.xml',
				$workDir . '/core/database/schema.xml',
				$workDir . '/vendor/keeko/core/database/schema.xml'
			];
			foreach ($schemas as $path) {
				if (file_exists($path)) {
					$schema = $path;
					break;
				}
			}
			$this->schema = $schema;
				
			if ($schema === null) {
				$locations = implode(', ', $schemas);
				throw new \RuntimeException(sprintf('Can\'t find schema in these locations: %s', $locations));
			}
		}

		return $this->schema;
	}
	
	public function isCoreSchema() {
		return strpos($this->getSchema(), 'core') !== false;
	}
	
	public function hasSchema() {
		$vendorName = $this->packageService->getPackage()->getVendor();
		return $this->getSchema() !== null && ($this->isCoreSchema() ? $vendorName == 'keeko' : true);
	}
	
	/**
	 * Returns the propel database
	 *
	 * @return Database
	 */
	public function getDatabase() {
		if ($this->database === null) {
			$builder = new QuickBuilder();
			$builder->setSchema(file_get_contents($this->getSchema()));
			$this->database = $builder->getDatabase();
		}
	
		return $this->database;
	}
	
	/**
	 * Returns the tableName for a given name
	 *
	 * @param String $name tableName or modelName
	 * @return String tableName
	 */
	public function getTableName($name) {
		$db = $this->getDatabase();
		if (!Text::create($name)->startsWith($db->getTablePrefix())) {
			$name = $db->getTablePrefix() . $name;
		}
	
		return $name;
	}
	
	/**
	 * Returns all model names
	 *
	 * @return String[] an array of modelName
	 */
	public function getModelNames() {
		$names = [];
		$database = $this->getDatabase();
		foreach ($database->getTables() as $table) {
			$names[] = $table->getOriginCommonName();
		}
	
		return $names;
	}
	
	/**
	 * Returns the propel models from the database, where table namespace matches package namespace
	 *
	 * @return ArrayList<Table>
	 */
	public function getModels() {
		if ($this->models === null) {
			$namespace = str_replace('\\\\', '\\', $this->getRootNamespace() . '\\model');
			$propel = $this->getDatabase();
	
			$this->models = new ArrayList();
	
			foreach ($propel->getTables() as $table) {
				if (!$table->isCrossRef() && $table->getNamespace() == $namespace) {
					$this->models->add($table);
				}
			}
		}
	
		return $this->models;
	}

	/**
	 * Returns the model for the given name
	 *
	 * @param String $name modelName or tableName
	 * @return Table
	 */
	public function getModel($name) {
		$tableName = $this->getTableName($name);
		$db = $this->getDatabase();
// 		echo $db->getNamespace();
// 		foreach ($db->getTables() as $table) {
// 			echo $table->getName();
// 		}
		$table = $db->getTable($tableName);
	
		return $table;
	}
	
	/**
	 * Returns the model names for a given package
	 * 
	 * @param PackageSchema $package a package to search models for, if omitted global package is used
	 * @return array array with string of model names
	 */
	public function getPackageModelNames(PackageSchema $package = null) {
		if ($package === null) {
			$package = $this->packageService->getPackage();
		}
		
		$models = [];
		// if this is a core-module, find the related model
		if ($package->getVendor() == 'keeko' && $this->isCoreSchema()) {
			$model = $package->getName();
			if ($this->hasModel($model)) {
				$models []= $model;
			}
		}
		
		// anyway, generate all
		else {
			foreach ($this->getModels() as $model) {
				$models []= $model->getOriginCommonName();
			}
		}
		
		return $models;
	}
	
	/**
	 * Checks whether the given model exists
	 *
	 * @param String $name tableName or modelName
	 * @return boolean
	 */
	public function hasModel($name) {
		return $this->getDatabase()->hasTable($this->getTableName($name), true);
	}

	/**
	 * Returns the root namespace for this package
	 *
	 * @return string the namespace
	 */
	public function getRootNamespace() {
		if ($this->namespace === null) {
			$input = $this->io->getInput();
			$ns = $input->hasOption('namespace')
				? $input->getOption('namespace')
				: null;
			if ($ns === null) {
				$package = $this->service->getPackageService()->getPackage();
				$ns = NamespaceResolver::getNamespace('src', $package);
			}
				
			$this->namespace = $ns;
		}
	
		return $this->namespace;
	}

	/**
	 * Retrieves the model name for the given package in two steps:
	 * 
	 * 1. Check if it is passed as cli parameter
	 * 2. Retrieve it from the package name
	 *
	 * @return String
	 */
	public function getModelName() {
		$input = $this->io->getInput();
		$modelName = $input->hasOption('model') ? $input->getOption('model') : null;
		if ($modelName === null && $this->isCoreSchema()) {
			$package = $this->service->getPackageService()->getPackage();
			$packageName = $package->getName();

			if ($this->hasModel($packageName)) {
				$modelName = $packageName;
			}
		}
		return $modelName;
	}
	
	/**
	 * Parses the model name from a given action name
	 *
	 * @param ActionSchema $action
	 * @return String modelName
	 */
	public function getModelNameByAction(ActionSchema $action) {
		$actionName = $action->getName();
		$modelName = null;
		if (($pos = strpos($actionName, '-')) !== false) {
			$modelName = substr($actionName, 0, $pos);
		}
		return $modelName;
	}

	/**
	 * Returns the full model object name, including namespace
	 * 
	 * @param ActionSchema $action
	 * @return String fullModelObjectName
	 */
	public function getFullModelObjectName(ActionSchema $action) {
		$database = $this->getDatabase();
		$modelName = $this->getModelNameByAction($action);
		$model = $this->getModel($modelName);
		$modelObjectName = $model->getPhpName();

		return $database->getNamespace() . '\\' . $modelObjectName;
	}
	
	/**
	 * Returns the operation (verb) of the action (if existent)
	 * 
	 * @param ActionSchema $action
	 * @return string|null
	 */
	public function getOperationByAction(ActionSchema $action) {
		$actionName = $action->getName();
		$operation = null;
		if (($pos = strpos($actionName, '-')) !== false) {
			$operation = substr($actionName, $pos + 1);
		}
		return $operation;
	}
	
	/**
	 * Returns wether the given action refers to a model.
	 * 
	 * Examples:
	 * 
	 * Action: user-create => model: user
	 * Action: recover-password => no model
	 * 
	 * @param ActionSchema $action
	 * @return boolean
	 */
	public function isModelAction(ActionSchema $action) {
		$modelName = $this->getModelNameByAction($action);
		return $this->hasModel($modelName);
	}
	
	/**
	 * Returns whether this is a crud operation action
	 * (create, read, update, delete, list)
	 * 
	 * @param ActionSchema $action
	 * @return boolean
	 */
	public function isCrudAction(ActionSchema $action) {
		$operation = $this->getOperationByAction($action);
		
		return in_array($operation, ['create', 'read', 'update', 'delete', 'list']);
	}
	
	/**
	 * Returns all model relationships.
	 * 
	 * 
	 * The returned array looks like:
	 * [
	 * 		'one' => [
	 * 			[
	 * 				'type' => 'one',
	 * 				'fk' => $fk
	 * 			],
	 * 			[
	 * 				'type' => 'one',
	 * 				'fk' => $fk2
	 * 			],
	 * 			...
	 * 		],
	 * 		'many' => [
	 * 			[
	 * 				'type' => 'many',
	 * 				'fk' => $fk3,
	 * 				'cfk' => $cfk
	 * 			],
	 * 			[
	 * 				'type' => 'many',
	 * 				'fk' => $fk4,
	 * 				'cfk' => $cfk2
	 * 			],
	 * 			...
	 * 		],
	 * 		'all' => [...] // both of above
	 * ]
	 * 
	 * 
	 * @param Table $model
	 * @return array
	 */
	public function getRelationships(Table $model) {
		$all = [];
		
		// to-one relationships
		$one = [];
		$fks = $model->getForeignKeys();
		foreach ($fks as $fk) {
			$item = [
				'type' => 'one',
				'fk' => $fk
			];
			$one[] = $item;
			$all[] = $item;
		}
	
		// to-many relationships
		$many = [];
		$cfks = $model->getCrossFks();
		foreach ($cfks as $cfk) {
			$foreign = null;
			$local = null;
			foreach ($cfk->getMiddleTable()->getForeignKeys() as $fk) {
				if ($fk->getForeignTable() != $model) {
					$foreign = $fk;
				} else if ($fk->getForeignTable() == $model) {
					$local = $fk;
				}
			}
			$item = [
				'type' => 'many',
				'lk' => $local,
				'fk' => $foreign,
				'cfk' => $cfk
			];
			$many[] = $item;
			$all[] = $item;
			break;
		}
	
		return [
			'one' => $one,
			'many' => $many,
			'all' => $all,
			'count' => count($all)
		];
	}
}
