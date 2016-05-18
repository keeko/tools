<?php
namespace keeko\tools\generator\ember;

use keeko\tools\generator\AbstractCodeGenerator;
use Propel\Generator\Model\Table;
use keeko\tools\services\CommandService;
use keeko\tools\model\Project;
use keeko\framework\schema\CodegenSchema;
use keeko\framework\utils\NameUtils;
use phootwork\collection\Set;

class EmberModelGenerator extends AbstractCodeGenerator {
	
	private $prj;
	
	public function __construct(CommandService $service, Project $project) {
		parent::__construct($service);
		$this->prj = $project;
	}
	
	public function generate(Table $model) {
		$class = new EmberClassGenerator('Model');
		$class->addImport('Model', 'ember-data/model');
		$class->addImport('attr', 'ember-data/attr');
		
		// columns
		$this->generateColumns($class, $model);
		
		// relationships
		$this->generateRelationships($class, $model);
		
		return $class->generate();
	}
	
	protected function generateColumns(EmberClassGenerator $class, Table $model) {
		$codegen = $this->getCodegen();
		$filter = $this->getColumnFilter($codegen, $model);
		foreach ($model->getColumns() as $col) {
			if (in_array($col, $filter)) {
				continue;
			}
				
			$prop = NameUtils::toCamelCase($col->getPhpName());
				
			switch ($col->getType()) {
				case 'NUMERIC':
				case 'DECIMAL':
				case 'TINYINT':
				case 'SMALLINT':
				case 'INTEGER':
				case 'BIGINT':
				case 'REAL':
				case 'FLOAT':
				case 'DOUBLE':
					$value = 'attr(\'number\')';
					break;
						
				case 'BOOLEAN':
					$value = 'attr(\'boolean\')';
					break;
						
				case 'DATE':
					$value = 'attr(\'date\')';
					break;
						
				default:
					$value = 'attr(\'string\')';
			}
				
			$class->setProperty($prop, $value);
		}
	}
	
	protected function generateRelationships(EmberClassGenerator $class, Table $model) {
		$relationships = $this->modelService->getRelationships($model);
		$imports = new Set();
		
		foreach ($relationships->getAll() as $relationship) {
			$prop = NameUtils::toCamelCase($relationship->getRelatedTypeName());
			$type = NameUtils::dasherize($relationship->getForeign()->getOriginCommonName());
			$slug = $this->getSlug($relationship->getForeign());
			
			// check one-to-one
			$oneToOne = false;
			if ($relationship->getType() == 'one') {
				$foreign = $relationship->getForeign();
				$rel = $this->modelService->getRelationship($foreign, $model->getOriginCommonName());
				$oneToOne = $rel != null;
			}
			
			if ($oneToOne) {
				$value = sprintf('belongsTo(\'%s/%s\')', $slug, $type);
				$imports->add('belongsTo');
			} else {
				$value = sprintf('hasMany(\'%s/%s\')', $slug, $type);
				$imports->add('hasMany');
			}
			
			$class->setProperty($prop, $value);
		}
		
		if ($imports->size() > 0) {
			$import = sprintf('{ %s }', implode(', ', $imports->toArray()));
			$class->addImport($import, 'ember-data/relationships');
		}
	}
	
	protected function getSlug(Table $model) {
		$namespace = $model->getNamespace();
		$parts = explode('\\', $namespace);
		
		if ($parts[0] == 'keeko') {
			return $parts[1];
		}
		
		return $parts[0] . '.' . $parts[1];
	}
	
	/**
	 * @return CodegenSchema
	 */
	private function getCodegen() {
		if ($this->prj->hasCodegenFile()) {
			return CodegenSchema::fromFile($this->prj->getCodegenFileName());
		}
		
		return new CodegenSchema();
	}
	
	private function getColumnFilter(CodegenSchema $codegen, Table $model) {
		$read = $codegen->getReadFilter($model->getOriginCommonName());
		$write = $codegen->getWriteFilter($model->getOriginCommonName());
		
		$merge = [];
		foreach ($read as $field) {
			if (in_array($field, $write)) {
				$merge[] = $field;
			}
		}
		
		return $merge;
	}
}