<?php
namespace keeko\tools\services;

use keeko\framework\schema\ActionSchema;
use keeko\framework\schema\PackageSchema;
use keeko\tools\model\Project;
use keeko\tools\model\Relationship;
use keeko\tools\model\Relationships;
use phootwork\collection\Map;
use phootwork\collection\Set;
use phootwork\file\Path;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Table;

class ModelService extends AbstractService {

	private $models = null;
	private $schema = null;
	
	/** @var Database */
	private $database = null;
	
	private $relationships = null;
	
	private $reader = null;
	
	public function read(Project $project = null) {
		if ($project === null) {
			if ($this->project->hasSchemaFile()) {
				$project = $this->project;
			} else {
				$project = new Project($this->project->getRootPath() . '/vendor/keeko/core');
			}
		}
		$this->reader = new ModelReader($project, $this->service);
	}
	
	/**
	 * @return ModelReader
	 */
	private function getReader() {
		if ($this->reader === null) {
			$this->read();
		}
		return $this->reader;
	}

	/**
	 * Returns the propel schema. The three locations, where the schema is looked up in:
	 *
	 * @return string|null the path to the schema
	 */
	public function getSchema() {
		if ($this->getReader()->getProject()->hasSchemaFile()) {
			return $this->getReader()->getProject()->getSchemaFileName();
		}

		return null;
	}
	
	public function isCoreSchema() {
		return $this->getReader()->getProject()->getPackage()->getFullName() == 'keeko/core';
	}
	
	public function hasSchema() {
		return $this->getReader()->getProject()->hasSchemaFile();
	}

	/**
	 * Returns the propel database
	 *
	 * @return Database
	 */
	public function getDatabase() {
		return $this->getReader()->getDatabase();
	}
	
	/**
	 * Returns the tableName for a given name
	 *
	 * @param String $name tableName or modelName
	 * @return String tableName
	 */
	public function getTableName($name) {
		return $this->getReader()->getTableName($name);
	}
	
	/**
	 * Returns all model names
	 *
	 * @return Set
	 */
	public function getModelNames() {
		return $this->getReader()->getModelNames();
	}
	
	/**
	 * Returns the propel models from the database, where table namespace matches package namespace
	 *
	 * @return Map
	 */
	public function getModels() {
		return $this->getReader()->getModels();
	}

	/**
	 * Returns the model for the given name
	 *
	 * @param String $name modelName or tableName
	 * @return Table
	 */
	public function getModel($name) {
		return $this->getReader()->getModel($name);
	}

// 	/**
// 	 * Returns the model names for a given package
// 	 * 
// 	 * @param PackageSchema $package a package to search models for, if omitted global package is used
// 	 * @return array array with string of model names
// 	 */
// 	public function getPackageModelNames(PackageSchema $package = null) {
// 		if ($package === null) {
// 			$package = $this->packageService->getPackage();
// 		}
		
// 		$models = [];
// 		// if this is a core-module, find the related model
// 		if ($package->getVendor() == 'keeko' && $this->isCoreSchema()) {
// 			$model = $package->getName();
// 			if ($this->hasModel($model)) {
// 				$models [] = $model;
// 			}
// 		}
		
// 		// anyway, generate all
// 		else {
// 			foreach ($this->getModels() as $model) {
// 				$models [] = $model->getOriginCommonName();
// 			}
// 		}
		
// 		return $models;
// 	}
	
	/**
	 * Checks whether the given model exists
	 *
	 * @param String $name tableName or modelName
	 * @return boolean
	 */
	public function hasModel($name) {
		return $this->getReader()->hasModel($name);
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
	 * Returns all model relationships.
	 * 
	 * @param Table $model
	 * @return Relationships
	 */
	public function getRelationships(Table $model) {
		return $this->getReader()->getRelationships($model);
	}
	
	/**
	 * Returns a relationship for a given related type name on a given model
	 * 
	 * @param Table $model
	 * @param string $relatedTypeName
	 * @return Relationship
	 */
	public function getRelationship(Table $model, $relatedTypeName) {
		$relationships = $this->getRelationships($model);
		return $relationships->get($relatedTypeName);
	}
}
