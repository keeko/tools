<?php
namespace keeko\tools\generator\ember;

use keeko\framework\schema\CodegenSchema;
use keeko\framework\utils\NameUtils;
use keeko\tools\model\Relationship;
use phootwork\collection\Set;
use Propel\Generator\Model\Table;

class EmberModelGenerator extends AbstractEmberGenerator {
	
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
			
			if ($col->isForeignKey() || $col->isPrimaryKey()) {
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
						
				case 'TIMESTAMP':
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
			$type = $relationship->getRelatedPluralTypeName();
			$slug = $this->getSlug($relationship->getForeign());
			
			// check one-to-one
			if ($relationship->getType() == Relationship::ONE_TO_ONE) {
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