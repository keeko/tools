<?php
namespace keeko\tools\helpers;

use keeko\tools\services\CommandService;
use Propel\Generator\Model\Database;
use phootwork\collection\ArrayList;
use Propel\Generator\Model\Table;
use keeko\core\schema\ActionSchema;

trait ModelServiceTrait {

	/**
	 * @return CommandService
	 */
	abstract protected function getService();

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
	protected function getSchema() {
		return $this->getService()->getModelService()->getSchema();
	}
	
	protected function isCoreSchema() {
		return $this->getService()->getModelService()->isCoreSchema();
	}
	
	protected function hasSchema() {
		return $this->getService()->getModelService()->hasSchema();
	}
	
	/**
	 * Returns the propel database
	 * 
	 * @return Database
	 */
	protected function getDatabase() {
		return $this->getService()->getModelService()->getDatabase();
	}
	
	/**
	 * Returns the tableName for a given name
	 * 
	 * @param String $name tableName or modelName
	 * @return String tableName 
	 */
	protected function getTableName($name) {
		return $this->getService()->getModelService()->getTableName($name);
	}
	
	/**
	 * Returns all model names
	 * 
	 * @return String[] an array of modelName 
	 */
	protected function getModelNames() {
		return $this->getService()->getModelService()->getModelNames();
	}
	
	/**
	 * Returns the propel models from the database, where table namespace matches package namespace
	 * 
	 * @return ArrayList<Table>
	 */
	protected function getModels() {
		return $this->getService()->getModelService()->getModels();
	}

	/**
	 * Returns the model for the given name
	 * 
	 * @param String $name modelName or tableName
	 * @return Table
	 */
	protected function getModel($name) {
		return $this->getService()->getModelService()->getModel($name);
	}
	
	/**
	 * Checks whether the given model exists
	 *
	 * @param String $name tableName or modelName
	 * @return boolean
	 */
	protected function hasModel($name) {
		return $this->getService()->getModelService()->hasModel($name);
	}

	/**
	 * Returns the root namespace for this package
	 * 
	 * @return string the namespace
	 */
	protected function getRootNamespace() {
		return $this->getService()->getModelService()->getRootNamespace();
	}
	
	/**
	 * Retrieves the model name for the given package
	 * 
	 * @return String
	 */
	protected function getModelName() {
		return $this->getService()->getModelService()->getModelName();
	}

	/**
	 * Parses the model name from a given action name
	 * 
	 * @param ActionSchema $action action
	 * @return String modelName
	 */
	protected function getModelNameByAction(ActionSchema $action) {
		return $this->getService()->getModelService()->getModelNameByAction($action);
	}
	
	/**
	 * Returns the full model name, including namespace
	 * 
	 * @param ActionSchema $action
	 * @return String modelName
	 */
	protected function getFullModelObjectName(ActionSchema $action) {
		return $this->getService()->getModelService()->getFullModelObjectName($action);
	}

}
