<?php
namespace keeko\tools\generator\domain;

use keeko\tools\generator\domain\AbstractDomainGenerator;
use Propel\Generator\Model\Table;
use gossi\codegen\model\PhpClass;
use phootwork\file\File;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;

class DomainGenerator extends AbstractDomainGenerator {
	
	public function generate(Table $model) {
		$class = $this->generateClass($model);
		$file = new File($this->codegenService->getFilename($class));
		
		// load from file, if exists
		if ($file->exists()) {
			$class = PhpClass::fromFile($file->getPathname());
		}
		
		$this->ensureUseStatements($class, $model);
		$this->generateApplyFilter($class, $model);
		$this->ensureDomainTrait($class, $model);
		
		return $class;
	}
	
	protected function generateClass(Table $model) {
		return PhpClass::create($this->getClassName($model))
			->setParentClassName('AbstractDomain')
			->addUseStatement('keeko\\framework\\foundation\\AbstractDomain')
		;
	}
	
	protected function getClassName(Table $model) {
		return str_replace('model', 'domain', $model->getNamespace()) . 
			'\\' . $model->getPhpName() . 'Domain';
	}
	
	protected function ensureUseStatements(PhpClass $class, Table $model) {
		$class->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName() . 'Query');
	}
	
	protected function generateApplyFilter(PhpClass $class, Table $model) {
		if (!$class->hasMethod('applyFilter')) {
			$class->setMethod(PhpMethod::create('applyFilter')
				->addParameter(PhpParameter::create('query')
					->setType($model->getPhpName() . 'Query')
				)
				->addParameter(PhpParameter::create('filter'))
				->setVisibility(PhpMethod::VISIBILITY_PROTECTED)
			);
		}
	}
	
	protected function ensureDomainTrait(PhpClass $class, Table $model) {
		$class->addUseStatement(str_replace('model', 'domain\\base', $model->getNamespace()) . 
			'\\' . $model->getPhpName() . 'DomainTrait');
		$class->addTrait($model->getPhpName() . 'DomainTrait');
	}
}