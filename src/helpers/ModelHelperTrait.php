<?php
namespace keeko\tools\helpers;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Propel\Generator\Util\QuickBuilder;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Table;

trait ModelHelperTrait {

	private $models = null;
	private $schema = null;
	private $namespace = null;

	/** @var Database */
	private $database = null;
	
	abstract protected function getPackage();
	
	abstract protected function getPackageVendor();
	
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
		if ($this->schema === null) {
			$schema = null;
			$schemas = [
				$this->getInput()->hasOption('schema') ? $this->getInput()->getOption('schema') : '',
				getcwd() . '/database/schema.xml',
				getcwd() . '/core/database/schema.xml'
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
	
	protected function isCoreSchema() {
		return strpos($this->getSchema(), 'core') !== false;
	}
	
	protected function hasSchema() {
		return $this->getSchema() !== null && ($this->isCoreSchema() ? $this->getPackageVendor() == 'keeko' : true);
	}
	
	/**
	 * Returns the propel database
	 * 
	 * @return Database
	 */
	protected function getDatabase() {
		if ($this->database === null) {
			$builder = new QuickBuilder();
			$builder->setSchema(file_get_contents($this->getSchema()));
			$this->database = $builder->getDatabase();
		}

		return $this->database;
	}
	
	/**
	 * Returns all model names
	 * 
	 * @return String[]
	 */
	protected function getModelNames() {
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
	 * @return Table[]
	 */
	protected function getModels() {
		if ($this->models === null) {
			$namespace = str_replace('\\\\', '\\', $this->getRootNamespace() . '\\model');
			$propel = $this->getDatabase();
		
			$this->models = [];
				
			foreach ($propel->getTables() as $table) {
				if (!$table->isCrossRef() && $table->getNamespace() == $namespace) {
					$this->models[] = $table;
				}
			}
		}

		return $this->models;
	}
	
	/**
	 * Returns the model for the given name
	 * 
	 * @param String $name
	 * @return Table
	 */
	protected function getModel($name) {
		$db = $this->getDatabase();
		$table = $db->getTable($name);
		
		if ($table === null) {
			$table = $db->getTable($db->getTablePrefix() . $name); 
		}

		return $table;
	}
	
	/**
	 * Checks whether the given model exists
	 *
	 * @param String $name
	 * @return boolean
	 */
	protected function hasModel($name) {
		return $this->getDatabase()->hasTable($name);
	}

	/**
	 * Returns the root namespace for this package
	 * 
	 * @return string the namespace
	 */
	protected function getRootNamespace() {
		if ($this->namespace === null) {
			$ns = $this->getInput()->hasOption('namespace') 
				? $this->getInput()->getOption('namespace') 
				: null;
			if ($ns === null) {
				$package = $this->getPackage();
					
				if (!isset($package['autoload'])) {
					throw new \DomainException(sprintf('No autoload for %s.', $package['name']));
				}
					
				if (!isset($package['autoload']['psr-4'])) {
					throw new \DomainException(sprintf('No psr-4 autoload for %s.', $package['name']));
				}
					
				foreach ($package['autoload']['psr-4'] as $namespace => $path) {
					if ($path === 'src' || $path === 'src/') {
						$ns = $namespace;
						break;
					}
				}
			}
			
			$this->namespace = $ns;
		}
	
		return $this->namespace;
	}
	
	/**
	 * 
	 * @return String
	 */
	protected function getModelName() {
		$model = $this->getInput()->hasOption('model') ? $this->getInput()->getOption('model') : null;
		if ($model === null) {
			$schema = $this->getSchema();
			if (strpos($schema, 'core') !== false) {
				$package = $this->getPackage();
				$name = substr($package['name'], strpos($package['name'], '/') + 1);
	
				$propel = $this->getDatabase();
				if ($propel->hasTable($name)) {
					$model = $name;
				}
			}
		}
		return $model;
	}

	
	protected function getModelNameByActionName($name) {
		$model = $this->getModelName();
		if ($model === null && ($pos = strpos($name, '-')) !== false) {
			$model = substr($name, 0, $pos);
		}
		return $model;
	}

}