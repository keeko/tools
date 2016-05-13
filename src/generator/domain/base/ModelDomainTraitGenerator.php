<?php
namespace keeko\tools\generator\domain\base;

use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpTrait;
use keeko\framework\utils\NameUtils;
use keeko\tools\model\ManyRelationship;
use keeko\tools\model\Relationship;
use Propel\Generator\Model\Table;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpProperty;

class ModelDomainTraitGenerator extends ReadOnlyModelDomainTraitGenerator {
	
	public function generate(Table $model) {
		$trait = parent::generate($model);
		
		// generate event
		$event = $this->generateEvent($model);
		$trait->addUseStatement($event->getQualifiedName());

		// generate CUD methods
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
	
	/**
	 * 
	 * @param Table $model
	 * @return PhpClass
	 */
	protected function generateEvent(Table $model) {
		$package = $this->packageService->getPackage();
		$slug = $package->getKeeko()->getModule()->getSlug();
		$modelName = $model->getOriginCommonName();
		
		$class = new PhpClass();
		$class->setNamespace(str_replace('model', 'event', $model->getNamespace()));
		$class->setName($model->getPhpName() . 'Event');
		$class->setParentClassName('Event');
		$class->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName());
		$class->addUseStatement('Symfony\Component\EventDispatcher\Event');
		
		// constants
		$class->setConstant('PRE_CREATE', sprintf('%s.%s.pre_create', $slug, $modelName));
		$class->setConstant('POST_CREATE', sprintf('%s.%s.post_create', $slug, $modelName));
		$class->setConstant('PRE_UPDATE', sprintf('%s.%s.pre_update', $slug, $modelName));
		$class->setConstant('POST_UPDATE', sprintf('%s.%s.post_update', $slug, $modelName));
		$class->setConstant('PRE_SAVE', sprintf('%s.%s.pre_save', $slug, $modelName));
		$class->setConstant('POST_SAVE', sprintf('%s.%s.post_save', $slug, $modelName));
		$class->setConstant('PRE_DELETE', sprintf('%s.%s.pre_delete', $slug, $modelName));
		$class->setConstant('POST_DELETE', sprintf('%s.%s.post_delete', $slug, $modelName));
		
		// generate relationship constants
		if (!$model->isReadOnly()) {
			$relationships = $this->modelService->getRelationships($model);
				
			// to-one relationships
			foreach ($relationships->getOne() as $one) {
				$snake = NameUtils::toSnakeCase($one->getRelatedName());
				$name = strtoupper($snake);
				$class->setConstant('PRE_' . $name . '_UPDATE', sprintf('%s.%s.pre_%s_update', $slug, $modelName, $snake));
				$class->setConstant('POST_' . $name . '_UPDATE', sprintf('%s.%s.post_%s_update', $slug, $modelName, $snake));
			}
		
			// to-many relationships
			foreach ($relationships->getMany() as $many) {
				$snake = NameUtils::toSnakeCase($many->getRelatedName());
				$name = strtoupper($snake);
				$class->setConstant('PRE_' . $name . '_ADD', sprintf('%s.%s.pre_%s_add', $slug, $modelName, $snake));
				$class->setConstant('POST_' . $name . '_ADD', sprintf('%s.%s.post_%s_add', $slug, $modelName, $snake));
				$class->setConstant('PRE_' . $name . '_UPDATE', sprintf('%s.%s.pre_%s_update', $slug, $modelName, $snake));
				$class->setConstant('POST_' . $name . '_UPDATE', sprintf('%s.%s.post_%s_update', $slug, $modelName, $snake));
				$class->setConstant('PRE_' . $name . '_REMOVE', sprintf('%s.%s.pre_%s_add', $slug, $modelName, $snake));
				$class->setConstant('POST_' . $name . '_REMOVE', sprintf('%s.%s.post_%s_add', $slug, $modelName, $snake));
			}
		}
		
		// properties
		$modelVariableName = $model->getCamelCaseName();
		$class->setProperty(PhpProperty::create($modelVariableName)
			->setType($model->getPackage())
			->setVisibility(PhpProperty::VISIBILITY_PROTECTED)
		);
		
		// constructor
		$class->setMethod(PhpMethod::create('__construct')
			->addParameter(PhpParameter::create($modelVariableName)->setType($model->getPhpName()))
			->setBody('$this->' . $modelVariableName . ' = $' . $modelVariableName .';')
		);
		
		// getModel()
		$class->setMethod(PhpMethod::create('get' . $model->getPhpName())
			->setType($model->getPhpName())
			->setBody('return $this->' . $modelVariableName .';')
		);
		
		$this->codegenService->dumpStruct($class, true);
		
		return $class;
	}
	
	protected function generateCreate(PhpTrait $trait, Table $model) {
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\Created');
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\NotFound');
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\NotValid');
	
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
				'local' => $relationship->getForeignKey()->getLocalColumn()->getPhpName(),
				'const' => strtoupper(NameUtils::toSnakeCase($relationship->getRelatedName()))
			]))
			->setType('PayloadInterface')
		);
	}
	
	protected function generateToManyRelationshipAdd(PhpTrait $trait, ManyRelationship $relationship) {
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\NotValid');
		
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
				'foreign_class' => $foreign->getPhpName(),
				'const' => strtoupper(NameUtils::toSnakeCase($relationship->getRelatedName()))
			]))
			->setType('PayloadInterface')
		);
	}
	
	protected function generateToManyRelationshipUpdate(PhpTrait $trait, ManyRelationship $relationship) {
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\NotValid');
		
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
				'middle_class' => $middle->getPhpName(),
				'const' => strtoupper(NameUtils::toSnakeCase($relationship->getRelatedName()))
			]))
			->setType('PayloadInterface')
		);
	}
	
	protected function generateToManyRelationshipRemove(PhpTrait $trait, ManyRelationship $relationship) {
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\NotValid');
		
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
				'foreign_class' => $foreign->getPhpName(),
				'const' => strtoupper(NameUtils::toSnakeCase($relationship->getRelatedName()))
			]))
			->setType('PayloadInterface')
		);
	}
}