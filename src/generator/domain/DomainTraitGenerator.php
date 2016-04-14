<?php
namespace keeko\tools\generator\domain;

use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpTrait;
use keeko\framework\utils\NameUtils;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;

class DomainTraitGenerator extends ReadOnlyDomainTraitGenerator {
	
	public function generate(Table $model) {
		$trait = parent::generate($model);
		
		$this->generateCreate($trait, $model);
		$this->generateUpdate($trait, $model);
		$this->generateDelete($trait, $model);
		
		// generate relationship methods
		if (!$model->isReadOnly()) {
			$relationships = $this->modelService->getRelationships($model);
			
			// to-one relationships
			foreach ($relationships['one'] as $one) {
				$fk = $one['fk'];
				$this->generateToOneRelationshipSet($trait, $model, $fk->getForeignTable(), $fk);
			}

			// to-many relationships
			foreach ($relationships['many'] as $many) {
				$fk = $many['fk'];
				$cfk = $many['cfk'];
				$this->generateToManyRelationshipAdd($trait, $model, $fk->getForeignTable(), $cfk->getMiddleTable());
				$this->generateToManyRelationshipUpdate($trait, $model, $fk->getForeignTable(), $cfk->getMiddleTable());
				$this->generateToManyRelationshipRemove($trait, $model, $fk->getForeignTable(), $cfk->getMiddleTable());
			}
		}

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
			->setType('PayloadInterface')
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
			->setType('PayloadInterface')
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
			->setType('PayloadInterface')
		);
	}
	
	protected function generateToOneRelationshipSet(PhpTrait $trait, Table $model, Table $foreign, ForeignKey $fk) {
		$name = $fk->getPhpName();
		if (empty($name)) {
			$name = $foreign->getPhpName();
		}
		$localId = NameUtils::toCamelCase($fk->getLocalColumn()->getPhpName());
		$trait->setMethod(PhpMethod::create('set' . $name . 'Id')
			->setDescription(str_replace('{foreign}', $foreign->getPhpName(), 'Sets the {foreign} id'))
			->addParameter(PhpParameter::create('id'))
			->addParameter(PhpParameter::create($localId))
			->setBody($this->twig->render('to-one-set.twig', [
				'model' => NameUtils::toCamelCase($model->getOriginCommonName()),
				'class' => $model->getPhpName(),
				'fk_id' => $localId,
				'local' => $fk->getLocalColumn()->getPhpName()
			]))
			->setType('PayloadInterface')
		);
	}
	
	protected function generateToManyRelationshipAdd(PhpTrait $trait, Table $model, Table $foreign, Table $middle) {
		$trait->addUseStatement($foreign->getNamespace() . '\\' . $foreign->getPhpName() . 'Query');
		$trait->setMethod(PhpMethod::create('add' . $foreign->getPhpName())
			->setDescription('Adds ' . $foreign->getPhpName() . ' to ' . $model->getPhpName())
			->addParameter(PhpParameter::create('id'))
			->addParameter(PhpParameter::create('data'))
			->setBody($this->twig->render('to-many-add.twig', [
				'model' => $model->getCamelCaseName(),
				'class' => $model->getPhpName(),				
				'foreign_model' => $foreign->getCamelCaseName(),
				'foreign_class' => $foreign->getPhpName()
			]))
			->setType('PayloadInterface')
		);
	}
	
	protected function generateToManyRelationshipUpdate(PhpTrait $trait, Table $model, Table $foreign, Table $middle) {
		$trait->addUseStatement($middle->getNamespace() . '\\' . $middle->getPhpName() . 'Query');
		$trait->addUseStatement($foreign->getNamespace() . '\\' . $foreign->getPhpName() . 'Query');
		$trait->setMethod(PhpMethod::create('update' . $foreign->getPhpName())
			->setDescription('Updates ' . $foreign->getPhpName() . ' on ' . $model->getPhpName())
			->addParameter(PhpParameter::create('id'))
			->addParameter(PhpParameter::create('data'))
			->setBody($this->twig->render('to-many-update.twig', [
				'model' => $model->getCamelCaseName(),
				'class' => $model->getPhpName(),
				'foreign_model' => $foreign->getCamelCaseName(),
				'foreign_class' => $foreign->getPhpName(),
				'middle_class' => $middle->getPhpName()
			]))
			->setType('PayloadInterface')
		);
	}
	
	protected function generateToManyRelationshipRemove(PhpTrait $trait, Table $model, Table $foreign, Table $middle) {
		$trait->addUseStatement($foreign->getNamespace() . '\\' . $foreign->getPhpName() . 'Query');
		$trait->setMethod(PhpMethod::create('remove' . $foreign->getPhpName())
			->setDescription('Removes ' . $foreign->getPhpName() . ' from ' . $model->getPhpName())
			->addParameter(PhpParameter::create('id'))
			->addParameter(PhpParameter::create('data'))
			->setBody($this->twig->render('to-many-remove.twig', [
				'model' => $model->getCamelCaseName(),
				'class' => $model->getPhpName(),
				'foreign_model' => $foreign->getCamelCaseName(),
				'foreign_class' => $foreign->getPhpName()
			]))
			->setType('PayloadInterface')
		);
	}
}