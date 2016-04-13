<?php
namespace keeko\tools\generator\domain;

use Propel\Generator\Model\Table;
use gossi\codegen\model\PhpTrait;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use keeko\framework\utils\NameUtils;

class ReadOnlyDomainTraitGenerator extends AbstractDomainGenerator {
	
	public function generate(Table $model) {
		$trait = $this->generateTrait($model);
		
		$this->generateRead($trait, $model);
		$this->generatePaginate($trait, $model);
		
		return $trait;
	}
	
	protected function generateTrait(Table $model) {
		$trait = PhpTrait::create()
			->setNamespace(str_replace('model', 'domain\\base', $model->getNamespace()))
			->setName($model->getPhpName() . 'DomainTrait')
			->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName())
			->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName() . 'Query')
			->addUseStatement('keeko\\framework\\service\\ServiceContainer')
			->setMethod(PhpMethod::create('getServiceContainer')
				->setVisibility(PhpMethod::VISIBILITY_PROTECTED)
				->setAbstract(true)
				->setType('ServiceContainer')
				->setDescription('Returns the service container')
			)
		;

		return $trait;
	}
	
	protected function generateRead(PhpTrait $trait, Table $model) {
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\Found');
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\NotFound');
		
		$trait->setMethod(PhpMethod::create('read')
			->addParameter(PhpParameter::create('id'))
			->setBody($this->twig->render('read.twig', [
				'model' => NameUtils::toCamelCase($model->getOriginCommonName()),
				'class' => $model->getPhpName()
			]))
			->setDescription('Returns one ' . $model->getPhpName() . ' with the given id')
		);
	}
	
	protected function generatePaginate(PhpTrait $trait, Table $model) {
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\Found');
		$trait->addUseStatement('Tobscure\\JsonApi\\Parameters');
		$trait->addUseStatement('keeko\\framework\\utils\\NameUtils');
		
		$trait->setMethod(PhpMethod::create('paginate')
			->addParameter(PhpParameter::create('params')
				->setType('Parameters')
			)
			->setBody($this->twig->render('paginate.twig', [
				'model' => NameUtils::toCamelCase($model->getOriginCommonName()),
				'class' => $model->getPhpName()
			]))
			->setDescription('Returns a paginated result')
		);
		
		$trait->setMethod(PhpMethod::create('applyFilter')
			->addParameter(PhpParameter::create('query')
				->setType($model->getPhpName() . 'Query')
			)
			->addParameter(PhpParameter::create('filter'))	
			->setVisibility(PhpMethod::VISIBILITY_PROTECTED)
			->setAbstract(true)
			->setDescription('Implement this functionality at ' . $this->getClassName($model))
		);
	}
	
	protected function getClassName(Table $model) {
		return str_replace('model', 'domain', $model->getNamespace()) .
			'\\' . $model->getPhpName() . 'Domain';
	}
}
