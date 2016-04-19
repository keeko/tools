<?php
namespace keeko\tools\generator\domain\base;

use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpTrait;
use keeko\framework\utils\NameUtils;
use keeko\tools\generator\domain\AbstractDomainGenerator;
use Propel\Generator\Model\Table;
use gossi\codegen\model\PhpProperty;

class ReadOnlyModelDomainTraitGenerator extends AbstractDomainGenerator {
	
	public function generate(Table $model) {
		$trait = $this->generateTrait($model);
		
		$this->generateGet($trait, $model);
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
		$trait->addUseStatement('keeko\framework\domain\payload\PayloadInterface');
		$trait->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName());

		return $trait;
	}
	
	protected function generateGet(PhpTrait $trait, Table $model) {
		$trait->addUseStatement('phootwork\collection\Map');
		$trait->setProperty(PhpProperty::create('pool')
			->setVisibility(PhpProperty::VISIBILITY_PROTECTED)
		);
		$trait->setMethod(PhpMethod::create('get')
			->addParameter(PhpParameter::create('id'))
			->setBody($this->twig->render('get.twig', [
				'model' => NameUtils::toCamelCase($model->getOriginCommonName()),
				'class' => $model->getPhpName()
			]))
			->setVisibility(PhpMethod::VISIBILITY_PROTECTED)
			->setDescription('Returns one ' . $model->getPhpName() . ' with the given id from cache')
			->setType($model->getPhpName() . '|null')
		);
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
			->setType('PayloadInterface')
		);
	}
	
	protected function generatePaginate(PhpTrait $trait, Table $model) {
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\Found');
		$trait->addUseStatement('keeko\\framework\\utils\\Parameters');
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
			->setType('PayloadInterface')
		);
		
		$trait->setMethod(PhpMethod::create('applyFilter')
			->addParameter(PhpParameter::create('query')
				->setType($model->getPhpName() . 'Query')
			)
			->addParameter(PhpParameter::create('filter'))
			->setVisibility(PhpMethod::VISIBILITY_PROTECTED)
			->setAbstract(true)
			->setType('void')
			->setDescription('Implement this functionality at ' . $this->getClassName($model))
		);
	}
	
	protected function getClassName(Table $model) {
		return str_replace('model', 'domain', $model->getNamespace()) .
			'\\' . $model->getPhpName() . 'Domain';
	}
}
