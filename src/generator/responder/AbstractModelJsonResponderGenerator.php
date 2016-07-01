<?php
namespace keeko\tools\generator\responder;

use keeko\framework\utils\NameUtils;
use Propel\Generator\Model\Table;
use keeko\tools\model\Relationship;

class AbstractModelJsonResponderGenerator extends AbstractJsonResponderGenerator {

	use PayloadGeneratorTrait;

	protected function getRelationshipIncludes(Table $model, $root = '', $processed = []) {
		if (in_array($model->getOriginCommonName(), $processed)) {
			return [];
		}

		$processed[] = $model->getOriginCommonName();

		$relationships = $this->modelService->getRelationships($model);
		$includes = [];

		foreach ($relationships->getAll() as $rel) {
			$typeName = $rel->getRelatedTypeName();
			if ($rel->getType() != Relationship::ONE_TO_ONE) {
				$typeName = NameUtils::pluralize($typeName);
			}
			$includeName = (!empty($root) ? $root . '.' : '') . $typeName;
			$includes[] = $includeName;

			if ($rel->getType() == Relationship::MANY_TO_MANY && $rel->getForeign() == $rel->getModel()) {
				$typeName = NameUtils::pluralize($rel->getReverseRelatedTypeName());
				$includeName = (!empty($root) ? $root . '.' : '') . $typeName;
				$includes[] = $includeName;
			}

// 			$foreign = $rel->getForeign();
// 			$includes = array_merge($includes, $this->getRelationshipIncludes($foreign, $includeName, $processed));
		}

		// load additional includes from codegen
		$generatorDefinition = $this->project->getGeneratorDefinition();
		$includes = array_merge($includes, $generatorDefinition->getIncludes($model->getOriginCommonName())->toArray());

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

			if ($rel->getType() === Relationship::MANY_TO_MANY && $rel->getModel() == $rel->getForeign()) {
				$typeName = $rel->getReverseRelatedTypeName();
				$name = (!empty($root) ? $root . '.' : '') . $typeName;

				$fields[$name] = $foreign;
				$fields = array_merge($fields, $this->getModelFields($foreign, $name, $processed));
			}
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
