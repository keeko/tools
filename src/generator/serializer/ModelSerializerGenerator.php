<?php
namespace keeko\tools\generator\serializer;

use gossi\codegen\model\PhpClass;
use Propel\Generator\Model\Table;

class ModelSerializerGenerator extends AbstractSerializerGenerator {
	
	public function generate(Table $model) {
		$class = $this->generateClass($this->getClassName($model));
		$class = $this->loadClass($class);
		$this->ensureBasicSetup($class);
		$this->ensureDomainTrait($class, $model);
		
		return $class;
	}
	
	protected function ensureBasicSetup(PhpClass $class) {
		parent::ensureBasicSetup($class);
		$class->setParentClassName('AbstractModelSerializer');
	}
	
	protected function ensureUseStatements(PhpClass $class) {
		parent::ensureUseStatements($class);
		$class->removeUseStatement('keeko\\framework\\model\\AbstractSerializer');
		$class->addUseStatement('keeko\\framework\\model\\AbstractModelSerializer');
	}
	
	protected function getClassName(Table $model) {
		return str_replace('model', 'serializer', $model->getNamespace()) . 
			'\\' . $model->getPhpName() . 'Serializer';
	}

	protected function ensureDomainTrait(PhpClass $class, Table $model) {
		$class->addUseStatement(str_replace('model', 'serializer\\base', $model->getNamespace()) . 
			'\\' . $model->getPhpName() . 'SerializerTrait');
		$class->addTrait($model->getPhpName() . 'SerializerTrait');
	}
}