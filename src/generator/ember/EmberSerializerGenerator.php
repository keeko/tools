<?php
namespace keeko\tools\generator\ember;

use Propel\Generator\Model\Table;
use keeko\framework\utils\NameUtils;

class EmberSerializerGenerator extends AbstractEmberGenerator {

	public function generate(Table $model) {
		$filter = $this->codegenService->getWriteFilter($model);
		if (count($filter) == 0) {
			return null;
		}
		$class = new EmberClassGenerator('JSONAPISerializer');
		$class->addImport('JSONAPISerializer', 'ember-data/serializers/json-api');

		$attrs = '';
		foreach ($filter as $field) {
			$attrs .= sprintf("\t%s: {serialize: false},\n", NameUtils::toCamelCase($field));
		}
		$attrs = "{\n\t" . rtrim($attrs, "\n,") . "\n}";
		$class->setProperty('attrs', $attrs);

		return $class->generate();
	}
}