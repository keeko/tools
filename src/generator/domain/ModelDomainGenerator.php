<?php
namespace keeko\tools\generator\domain;

use gossi\codegen\model\PhpClass;
use Propel\Generator\Model\Table;

class ModelDomainGenerator extends AbstractDomainGenerator {
	
	public function generate(Table $model) {
		$class = $this->generateClass($this->getClassName($model));
		$class = $this->loadClass($class);
		$this->ensureBasicSetup($class);
		$this->ensureDomainTrait($class, $model);
		
		return $class;
	}
	
	protected function getClassName(Table $model) {
		return str_replace('model', 'domain', $model->getNamespace()) . 
			'\\' . $model->getPhpName() . 'Domain';
	}
	
	protected function ensureDomainTrait(PhpClass $class, Table $model) {
		$class->addUseStatement(str_replace('model', 'domain\\base', $model->getNamespace()) . 
			'\\' . $model->getPhpName() . 'DomainTrait');
		$class->addTrait($model->getPhpName() . 'DomainTrait');
	}
}