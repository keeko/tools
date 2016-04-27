<?php
namespace keeko\tools\generator\responder;

use keeko\framework\utils\NameUtils;
use Propel\Generator\Model\Table;

class AbstractModelJsonResponderGenerator extends AbstractJsonResponderGenerator {
	
	use PayloadGeneratorTrait;
	
	protected function getRelationshipIncludes(Table $model, $root = '', $processed = []) {
		if (in_array($model->getOriginCommonName(), $processed)) {
			return [];
		}
		
		$relationships = $this->modelService->getRelationships($model);
		$includes = [];
	
		foreach ($relationships->getAll() as $rel) {
			$foreign = $rel->getForeign();
			$processed[] = $foreign->getOriginCommonName();
			
			$typeName = $rel->getRelatedTypeName();
			$includes[] = (!empty($root) ? $root . '.' : '') . $typeName;
			$includes = array_merge($includes, $this->getRelationshipIncludes($foreign, $typeName, $processed));
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

		foreach ($relationships->getAll() as $rel) {
			$foreign = $rel->getForeign();
			$processed[] = $foreign->getOriginCommonName();
			
			$typeName = $rel->getRelatedTypeName();
			$name = (!empty($root) ? $root . '.' : '') . $typeName;
			
			$fields[$name] = $foreign;
			$fields = array_merge($fields, $this->getModelFields($foreign, $name, $processed));
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
