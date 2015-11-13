<?php
namespace keeko\tools\services;

use phootwork\collection\ArrayList;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Database;
use Propel\Generator\Util\QuickBuilder;
use phootwork\lang\Text;
use keeko\core\schema\ActionSchema;
use keeko\tools\utils\NamespaceResolver;

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
					$workDir . '/core/database/schema.xml'
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
		$vendorName = $this->service->getPackageService()->getVendorName();
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
	 * Retrieves the model name for the given package
	 *
	 * @return String
	 */
	public function getModelName() {
		$input = $this->io->getInput();
		$model = $input->hasOption('model') ? $input->getOption('model') : null;
		if ($model === null) {
			$schema = $this->getSchema();
			if (strpos($schema, 'core') !== false) {
				$package = $this->service->getPackageService()->getPackage();
				$name = substr($package['name'], strpos($package['name'], '/') + 1);
	
				if ($this->hasModel($name)) {
					$model = $name;
				}
			}
		}
		return $model;
	}
	
	/**
	 * Parses the model name from a given action name
	 *
	 * @param ActionSchema $action
	 * @return String modelName
	 */
	public function getModelNameByAction(ActionSchema $action) {
		$name = $action->getName();
		$model = $this->getModelName();
		if ($model === null && ($pos = strpos($name, '-')) !== false) {
			$model = substr($name, 0, $pos);
		}
		return $model;
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
}
