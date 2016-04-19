<?php
namespace keeko\tools\generator\domain\base;

use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpTrait;
use keeko\framework\utils\NameUtils;
use keeko\tools\model\ManyRelationship;
use keeko\tools\model\Relationship;
use Propel\Generator\Model\Table;

class ModelDomainTraitGenerator extends ReadOnlyModelDomainTraitGenerator {
	
	public function generate(Table $model) {
		$trait = parent::generate($model);
		
		$this->generateCreate($trait, $model);
		$this->generateUpdate($trait, $model);
		$this->generateDelete($trait, $model);
		
		// generate relationship methods
		if (!$model->isReadOnly()) {
			$relationships = $this->modelService->getRelationships($model);
			
			// to-one relationships
			foreach ($relationships->getOne() as $one) {
				$this->generateToOneRelationshipSet($trait, $one);
			}

			// to-many relationships
			foreach ($relationships->getMany() as $many) {
				$this->generateToManyRelationshipAdd($trait, $many);
				$this->generateToManyRelationshipUpdate($trait, $many);
				$this->generateToManyRelationshipRemove($trait, $many);
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
	
	protected function generateToOneRelationshipSet(PhpTrait $trait, Relationship $relationship) {
		$model = $relationship->getModel();
		$foreign = $relationship->getForeign();
		$name = $relationship->getRelatedName();
		$localId = NameUtils::toCamelCase($name) . 'Id';
		$trait->setMethod(PhpMethod::create('set' . $name . 'Id')
			->setDescription(str_replace('{foreign}', $foreign->getPhpName(), 'Sets the {foreign} id'))
			->addParameter(PhpParameter::create('id'))
			->addParameter(PhpParameter::create($localId))
			->setBody($this->twig->render('to-one-set.twig', [
				'model' => NameUtils::toCamelCase($model->getOriginCommonName()),
				'class' => $model->getPhpName(),
				'fk_id' => $localId,
				'local' => $relationship->getForeignKey()->getLocalColumn()->getPhpName()
			]))
			->setType('PayloadInterface')
		);
	}
	
	protected function generateToManyRelationshipAdd(PhpTrait $trait, ManyRelationship $relationship) {
		$model = $relationship->getModel();
		$foreign = $relationship->getForeign();
		$trait->addUseStatement($foreign->getNamespace() . '\\' . $foreign->getPhpName() . 'Query');
		$trait->setMethod(PhpMethod::create('add' . $relationship->getRelatedName())
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
	
	protected function generateToManyRelationshipUpdate(PhpTrait $trait, ManyRelationship $relationship) {
		$model = $relationship->getModel();
		$foreign = $relationship->getForeign();
		$middle = $relationship->getMiddle();
		$trait->addUseStatement($middle->getNamespace() . '\\' . $middle->getPhpName() . 'Query');
		$trait->addUseStatement($foreign->getNamespace() . '\\' . $foreign->getPhpName() . 'Query');
		$trait->setMethod(PhpMethod::create('update' . $relationship->getRelatedName())
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
	
	protected function generateToManyRelationshipRemove(PhpTrait $trait, ManyRelationship $relationship) {
		$model = $relationship->getModel();
		$foreign = $relationship->getForeign();
		$trait->addUseStatement($foreign->getNamespace() . '\\' . $foreign->getPhpName() . 'Query');
		$trait->setMethod(PhpMethod::create('remove' . $relationship->getRelatedName())
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