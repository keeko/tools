<?php
namespace keeko\tools\generator\domain;

use gossi\codegen\model\PhpTrait;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use keeko\framework\utils\NameUtils;
use Propel\Generator\Model\Table;

class DomainTraitGenerator extends ReadOnlyDomainTraitGenerator {
	
	public function generate(Table $model) {
		$trait = parent::generate($model);
		
		$this->generateCreate($trait, $model);
		$this->generateUpdate($trait, $model);
		$this->generateDelete($trait, $model);

		return $trait;
	}
	
	protected function generateCreate(PhpTrait $trait, Table $model) {
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\Created');
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\NotFound');
	
		$trait->setMethod(PhpMethod::create('create')
			->addParameter(PhpParameter::create('data'))
			->setBody($this->twig->render('create.twig', [
				'model' => NameUtils::toCamelCase($model->getOriginCommonName()),
				'class' => $model->getPhpName()
			]))
			->setDescription('Creates a new ' . $model->getPhpName() . ' with the provided data')
		);
	}
	
	protected function generateUpdate(PhpTrait $trait, Table $model) {
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\Updated');
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\NotUpdated');
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\NotFound');
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\NotValid');
		
		$trait->setMethod(PhpMethod::create('update')
			->addParameter(PhpParameter::create('id'))
			->addParameter(PhpParameter::create('data'))
			->setBody($this->twig->render('update.twig', [
				'model' => NameUtils::toCamelCase($model->getOriginCommonName()),
				'class' => $model->getPhpName()
			]))
			->setDescription('Updates a ' . $model->getPhpName() . ' with the given id' .
				'and the provided data')
		);
	}
	
	protected function generateDelete(PhpTrait $trait, Table $model) {
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\Deleted');
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\NotDeleted');
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\NotFound');
		
		$trait->setMethod(PhpMethod::create('delete')
			->addParameter(PhpParameter::create('id'))
			->setBody($this->twig->render('delete.twig', [
				'model' => NameUtils::toCamelCase($model->getOriginCommonName()),
				'class' => $model->getPhpName()
			]))
			->setDescription('Deletes a ' . $model->getPhpName() . ' with the given id')
		);
	}

}