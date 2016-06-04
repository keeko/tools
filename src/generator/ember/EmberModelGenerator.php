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
			$default = null;

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
					$type = 'number';
					$defaultValue = $col->getDefaultValueString();
					if ($defaultValue != '0' && $defaultValue != 'null') {
						$default = $defaultValue;
					}
					break;

				case 'BOOLEAN':
					$type = 'boolean';
					$defaultValue = $col->getDefaultValueString();
					if ($defaultValue == 'false' || $defaultValue == 'true') {
						$default = $defaultValue;
					}
					break;

				case 'TIMESTAMP':
					$type = 'date';
					break;

				default:
					$type = 'string';
					$defaultValue = $col->getDefaultValueString();
					if ($defaultValue != 'null') {
						$default = $defaultValue;
					}
			}

			$value = sprintf('attr(\'%s\'%s)', $type,
				$default !== null ? ', {defaultValue: ' . $default . '}' : '');

			$class->setProperty($prop, $value);
		}
	}

	protected function generateRelationships(EmberClassGenerator $class, Table $model) {
		$relationships = $this->modelService->getRelationships($model);
		$imports = new Set();
		$inverses = $this->collectInverseRelationships($model);

		foreach ($relationships->getAll() as $relationship) {
			$type = NameUtils::dasherize($relationship->getForeign()->getOriginCommonName());
			$slug = $this->getSlug($relationship->getForeign());

			if ($relationship->getType() == Relationship::ONE_TO_ONE) {
				$prop = NameUtils::toCamelCase($relationship->getRelatedName());
				$inverse = isset($inverses[$prop]) ? ', {inverse: ' . $inverses[$prop] . '}' : '';
				$value = sprintf('belongsTo(\'%s/%s\'%s)', $slug, $type, $inverse);
				$imports->add('belongsTo');
			} else {
				$prop = NameUtils::toCamelCase($relationship->getRelatedPluralName());
				$inverse = isset($inverses[$prop]) ? ', {inverse: ' . $inverses[$prop] . '}' : '';
				$value = sprintf('hasMany(\'%s/%s\'%s)', $slug, $type, $inverse);
				$imports->add('hasMany');
			}

			$class->setProperty($prop, $value);

			if ($relationship->getType() == Relationship::MANY_TO_MANY && $relationship->isReflexive()) {
				$inverse = NameUtils::toCamelCase($relationship->getRelatedPluralName());
				$prop = NameUtils::toCamelCase($relationship->getReverseRelatedPluralTypeName());
				$value = sprintf('hasMany(\'%s/%s\', {inverse: \'%s\'})', $slug, $type, $inverse);
				$class->setProperty($prop, $value);
			}
		}

		if ($imports->size() > 0) {
			$import = sprintf('{ %s }', implode(', ', $imports->toArray()));
			$class->addImport($import, 'ember-data/relationships');
		}
	}

	private function collectInverseRelationships(Table $model) {
		$relationships = $this->modelService->getRelationships($model);
		$inverses = [];

		// find reflexive relationships
		foreach ($relationships->getAll() as $relationship) {

			// find reflexive one-to-many relationships
			if ($relationship->getType() == Relationship::ONE_TO_MANY && $relationship->isReflexive()) {
				$prop = NameUtils::toCamelCase($relationship->getRelatedPluralName());
				$inverses[$prop] = '\'' . NameUtils::toCamelCase($relationship->getReverseRelatedName()) . '\'';

				// reverse inverse
				$prop = NameUtils::toCamelCase($relationship->getReverseRelatedName());
				$inverses[$prop] = '\'' . NameUtils::toCamelCase($relationship->getRelatedPluralName()) . '\'';
			}

			// find reflexive many-to-many relationships
			if ($relationship->getType() == Relationship::MANY_TO_MANY && $relationship->isReflexive()) {
				$prop = NameUtils::toCamelCase($relationship->getRelatedPluralName());
				$inverses[$prop] = '\''.NameUtils::toCamelCase($relationship->getReverseRelatedPluralName()) . '\'';
			}
		}

		$dupes = [];
		foreach ($relationships->getAll() as $relationship) {
			$rels = $this->modelService->getRelationships($relationship->getForeign());

			// find null inverse one-to-one relationships
			foreach ($rels->getOneToOne() as $rel) {
				if ($rel->getForeign() == $model) {
					$prop = NameUtils::toCamelCase($relationship->getRelatedName());
					if (in_array($prop, $dupes) && !isset($inverses[$prop])) {
						$inverses[$prop] = 'null';
					} else {
						$dupes[] = $prop;
					}
				}
			}

			// find inverse one-to-many relationship
			foreach ($rels->getOneToMany() as $rel) {
				if ($rel->getForeign() == $model) {
					$prop = NameUtils::toCamelCase($rel->getReverseRelatedName());
					$inverses[$prop] = '\'' . NameUtils::toCamelCase($rel->getRelatedPluralName()) . '\'';
				}
			}
		}

		return $inverses;
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