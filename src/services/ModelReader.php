<?php
namespace keeko\tools\services;

use keeko\framework\schema\CodegenSchema;
use keeko\tools\command\GenerateActionCommand;
use keeko\tools\command\GenerateApiCommand;
use keeko\tools\command\GenerateDomainCommand;
use keeko\tools\command\GenerateEmberModelsCommand;
use keeko\tools\command\GenerateSerializerCommand;
use keeko\tools\model\ManyToManyRelationship;
use keeko\tools\model\OneToManyRelationship;
use keeko\tools\model\OneToOneRelationship;
use keeko\tools\model\Project;
use keeko\tools\model\Relationship;
use keeko\tools\model\Relationships;
use phootwork\collection\ArrayList;
use phootwork\collection\Map;
use phootwork\collection\Set;
use phootwork\lang\Text;
use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Table;
use keeko\tools\model\ReverseOneToOneRelationship;

class ModelReader {

	/** @var Project */
	private $project;

	/** @var Database */
	private $database;

	/** @var CodegenSchema */
	private $codegen;

	/** @var Map */
	private $relationships;

	/** @var Set */
	private $relationshipsLoaded;

	/** @var Map */
	private $models;

	/** @var Set */
	private $excluded;

	/** @var CommandService */
	private $service;

	public function __construct(Project $project, CommandService $service) {
		$this->project = $project;
		$this->service = $service;
		$this->relationships = new Map();
		$this->relationshipsLoaded = new Set();
		$this->codegen = $project->hasCodegenFile()
			? CodegenSchema::fromFile($project->getCodegenFileName())
			: new CodegenSchema();
		$this->excluded = $this->loadExcludedModels();

		$this->load();
	}

	private function loadExcludedModels() {
		$list = new ArrayList();
		$command = $this->service->getCommand();
		if ($command instanceof GenerateActionCommand) {
			$list = $this->codegen->getExcludedAction();
		} else if ($command instanceof GenerateApiCommand) {
			$list = $this->codegen->getExcludedApi();
		} else if ($command instanceof GenerateDomainCommand) {
			$list = $this->codegen->getExcludedDomain();
		} else if ($command instanceof GenerateEmberModelsCommand) {
			$list = $this->codegen->getExcludedEmber();
		} else if ($command instanceof GenerateSerializerCommand) {
			$list = $this->codegen->getExcludedSerializer();
		}

		return new Set($list);
	}

	public function getExcluded() {
		return $this->excluded;
	}

	public function getProject() {
		return $this->project;
	}

	private function load() {
		if ($this->project->hasSchemaFile()) {
			$this->loadDatabase();
			$this->loadRelationships();
		}
	}

	private function loadDatabase() {
		if ($this->database === null) {
			$dom = new \DOMDocument('1.0', 'UTF-8');
			$dom->load($this->project->getSchemaFileName());
			$this->includeExternalSchemas($dom);

			$config = new GeneratorConfig($this->project->getRootPath());
			$reader = new SchemaReader($config->getConfiguredPlatform());
			$reader->setGeneratorConfig($config);
			$schema = $reader->parseString($dom->saveXML(), $this->project->getSchemaFileName());
			$this->database = $schema->getDatabase();

			// extend excluded list with parents when using a certain behavior
			foreach ($this->database->getTables() as $table) {
				foreach ($table->getBehaviors() as $behavior) {

					switch ($behavior->getName()) {
						case 'concrete_inheritance':
							$parent = $behavior->getParameter('extends');
							$this->excluded->add($parent);
							$this->renameForeignKeys($table, $parent);
							break;

						case 'versionable':
							$versionTableName = $behavior->getParameter('version_table')
								? $behavior->getParameter('version_table')
								: ($table->getOriginCommonName() . '_version');

							$this->excluded->add($versionTableName);
							break;
					}
				}
			}
		}

		return $this->database;
	}

	private function renameForeignKeys(Table $table, $parent) {
		$parent = $this->getModel($parent);

		foreach ($table->getForeignKeys() as $fk) {
			// find fk in parent
			foreach ($parent->getForeignKeys() as $pfk) {
				if ($pfk->getForeignTableCommonName() == $fk->getForeignTableCommonName()
						&& $pfk->getLocalColumnName() == $fk->getLocalColumnName()
						&& $pfk->getForeignColumnName() == $fk->getForeignColumnName()) {

					// replace
					$name = new Text($pfk->getName());
					if ($name->contains($parent->getOriginCommonName())) {
						$name = $name->replace($parent->getOriginCommonName(), $table->getOriginCommonName());
						$fk->setName($name->toString());
					}
					break;
				}
			}
		}
	}

	private function includeExternalSchemas(\DOMDocument $dom) {
		$databaseNode = $dom->getElementsByTagName('database')->item(0);
		$externalSchemaNodes = $dom->getElementsByTagName('external-schema');

		while ($externalSchema = $externalSchemaNodes->item(0)) {
			$include = $externalSchema->getAttribute('filename');
			$externalSchema->parentNode->removeChild($externalSchema);

			if (!is_readable($include)) {
				throw new \RuntimeException("External schema '$include' does not exist");
			}

			$externalSchemaDom = new \DOMDocument('1.0', 'UTF-8');
			$externalSchemaDom->load(realpath($include));

			// The external schema may have external schemas of its own ; recurs
			$this->includeExternalSchemas($externalSchemaDom);
			foreach ($externalSchemaDom->getElementsByTagName('table') as $tableNode) {
				$databaseNode->appendChild($dom->importNode($tableNode, true));
			}
		}
	}

	public function getDatabase() {
		return $this->database;
	}

	private function loadRelationships() {
		foreach ($this->getModels() as $table) {
			$this->loadRelationshipsForModel($table);
		}
	}

	/**
	 * Returns all model relationships.
	 *
	 * @param Table $model
	 */
	private function loadRelationshipsForModel(Table $model) {
		if ($this->relationshipsLoaded->contains($model->getName())) {
			return;
		}

		if ($this->excluded->contains($model->getOriginCommonName())) {
			return;
		}

		$relationships = $this->getRelationships($model);
		$definition = $this->codegen->getRelationships($model->getOriginCommonName());

		// one-to-* relationships
		foreach ($model->getForeignKeys() as $fk) {
			// skip, if fk is excluded
			if ($this->excluded->contains($fk->getForeignTable()->getOriginCommonName())) {
				continue;
			}

			$type = Relationship::ONE_TO_MANY;
			if ($definition->has($fk->getName())) {
				$type = $definition->get($fk->getName());
			}

			$foreign = $fk->getForeignTable();

			switch ($type) {
				case Relationship::ONE_TO_ONE:
					$relationship = new OneToOneRelationship($model, $foreign, $fk);
					$relationships->add($relationship);

					$reverse = new ReverseOneToOneRelationship($foreign, $model, $fk);
					$relationship->setReverseRelationship($reverse);
					$this->getRelationships($foreign)->add($reverse);
					break;

				case Relationship::ONE_TO_MANY:
					$relationship = new OneToManyRelationship($foreign, $model, $fk);
					$this->getRelationships($foreign)->add($relationship);

					$reverse = new OneToOneRelationship($model, $foreign, $fk);
					$relationship->setReverseRelationship($reverse);
					$relationships->add($reverse);
					break;
			}
		}

		// many-to-many relationships
		foreach ($model->getCrossFks() as $cfk) {
			$relationship = new ManyToManyRelationship($model, $cfk);

			// skip, if fk is excluded
			if ($this->excluded->contains($relationship->getForeign()->getOriginCommonName())) {
				continue;
			}

			$relationships->add($relationship);
		}

		$this->relationships->set($model->getName(), $relationships);
		$this->relationshipsLoaded->add($model->getName());

		return $relationships;
	}

	/**
	 *
	 * @param Table $model
	 * @return Relationships
	 */
	public function getRelationships(Table $model) {
		if (!$this->relationships->has($model->getName())) {
			$this->relationships->set($model->getName(), new Relationships($model));
		}
		return $this->relationships->get($model->getName());
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
	 * Returns the model for the given name
	 *
	 * @param String $name modelName or tableName
	 * @return Table
	 */
	public function getModel($name) {
		$tableName = $this->getTableName($name);
		$db = $this->getDatabase();
		$table = $db->getTable($tableName);

		return $table;
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
	 * Returns the models from the database, where table namespace matches package namespace
	 *
	 * @return Map
	 */
	public function getModels() {
		if ($this->models === null) {
			$database = $this->getDatabase();
			$namespace = $database->getNamespace();

			$this->models = new Map();

			foreach ($database->getTables() as $table) {
				if (!$table->isCrossRef()
						&& $table->getNamespace() == $namespace
						&& !$this->excluded->contains($table->getOriginCommonName())) {
					$this->models->set($table->getOriginCommonName(), $table);
				}
			}
		}

		return $this->models;
	}

	/**
	 * Returns all model names
	 *
	 * @return Set
	 */
	public function getModelNames() {
		return $this->getModels()->keys();
	}
}