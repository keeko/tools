<?php
namespace keeko\tools\generator\response;

use keeko\framework\utils\NameUtils;
use Propel\Generator\Model\Table;

class AbstractModelJsonResponseGenerator extends AbstractJsonResponseGenerator {
	
	protected function getRelationshipIncludes(Table $model, $root = '', $processed = []) {
		if (in_array($model->getOriginCommonName(), $processed)) {
			return [];
		}
		
		$relationships = $this->modelService->getRelationships($model);
		$includes = [];
	
		foreach ($relationships['all'] as $rel) {
			$fk = $rel['fk'];
			$foreignModel = $fk->getForeignTable();
			$processed[] = $foreignModel->getOriginCommonName();
			
			$typeName = NameUtils::dasherize($fk->getForeignTable()->getOriginCommonName());
			if ($rel['type'] == 'many') {
				$typeName = NameUtils::pluralize($typeName);
			}
			$includes[] = (!empty($root) ? $root . '.' : '') . $typeName;
			$includes = array_merge($includes, $this->getRelationshipIncludes($foreignModel, $typeName, $processed));
		}
	
		return $includes;
	}
	
	protected function getModelFields(Table $model, $root = '', $processed = []) {
		if (in_array($model->getOriginCommonName(), $processed)) {
			return [];
		}
		
		$typeName = NameUtils::dasherize($model->getOriginCommonName());
		$relationships = $this->modelService->getRelationships($model);
		$fields = [$typeName => $model];

		foreach ($relationships['all'] as $rel) {
			$fk = $rel['fk'];
			$foreignModel = $fk->getForeignTable();
			$processed[] = $foreignModel->getOriginCommonName();
			
			$typeName = NameUtils::dasherize($fk->getForeignTable()->getOriginCommonName());
			if ($rel['type'] == 'many') {
				$typeName = NameUtils::pluralize($typeName);
			}
			$name = (!empty($root) ? $root . '.' : '') . $typeName;
			
			$fields[$name] = $foreignModel;
			$fields = array_merge($fields, $this->getModelFields($foreignModel, $name, $processed));
		}
		
		return $fields;
	}
	
	protected function getFieldsCode(array $fields) {
		$code = '';
		foreach ($fields as $typeName => $field) {
			$code .= sprintf("\t'%s' => %s::getSerializer()->getFields(),\n", $typeName, $field->getPhpName());
		}
		
		if (strlen($code) > 0) {
			$code = substr($code, 0, -2);
		}
		
		return sprintf("[\n%s\n]", $code);
	}
	
}
