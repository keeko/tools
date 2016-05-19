<?php
namespace keeko\tools\generator\domain\base;

use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpProperty;
use gossi\codegen\model\PhpTrait;
use keeko\framework\utils\NameUtils;
use keeko\tools\model\Relationship;
use Propel\Generator\Model\Table;
use keeko\tools\model\ManyToManyRelationship;
use keeko\tools\model\OneToManyRelationship;

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
			
			foreach ($relationships->getAll() as $relationship) {
				switch ($relationship->getType()) {
					case Relationship::ONE_TO_ONE:
						$this->generateToOneRelationshipSet($trait, $relationship);
						break;
						
					case Relationship::ONE_TO_MANY:
						$this->generateToManyRelationshipAdd($trait, $relationship);
						$this->generateToManyRelationshipRemove($trait, $relationship);
						$this->generateOneToManyRelationshipUpdate($trait, $relationship);
						break;
					
					case Relationship::MANY_TO_MANY:
						$this->generateToManyRelationshipAdd($trait, $relationship);
						$this->generateToManyRelationshipRemove($trait, $relationship);
						$this->generateManyToManyRelationshipUpdate($trait, $relationship);
						break;
				}
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
		if ($model->getPhpName() == 'Event') {
			$class->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName(), 'Model');
		} else {
			$class->addUseStatement($model->getNamespace() . '\\' . $model->getPhpName());
		}
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
			
			foreach ($relationships->getAll() as $relationship) {
				// one-to-one relationships
				if ($relationship->getType() == Relationship::ONE_TO_ONE) {
					$snake = NameUtils::toSnakeCase($relationship->getRelatedName());
					$name = strtoupper($snake);
					$class->setConstant('PRE_' . $name . '_UPDATE', sprintf('%s.%s.pre_%s_update', $slug, $modelName, $snake));
					$class->setConstant('POST_' . $name . '_UPDATE', sprintf('%s.%s.post_%s_update', $slug, $modelName, $snake));
				}
				
				// others
				else {
					$snake = NameUtils::toSnakeCase($relationship->getRelatedPluralName());
					$name = strtoupper($snake);
					$class->setConstant('PRE_' . $name . '_ADD', sprintf('%s.%s.pre_%s_add', $slug, $modelName, $snake));
					$class->setConstant('POST_' . $name . '_ADD', sprintf('%s.%s.post_%s_add', $slug, $modelName, $snake));
					$class->setConstant('PRE_' . $name . '_REMOVE', sprintf('%s.%s.pre_%s_add', $slug, $modelName, $snake));
					$class->setConstant('POST_' . $name . '_REMOVE', sprintf('%s.%s.post_%s_add', $slug, $modelName, $snake));
					$class->setConstant('PRE_' . $name . '_UPDATE', sprintf('%s.%s.pre_%s_update', $slug, $modelName, $snake));
					$class->setConstant('POST_' . $name . '_UPDATE', sprintf('%s.%s.post_%s_update', $slug, $modelName, $snake));
				}
			}
		}
		
		// properties
		$modelVariableName = $model->getCamelCaseName();
		$class->setProperty(PhpProperty::create($modelVariableName)
			->setType($model->getPackage())
			->setVisibility(PhpProperty::VISIBILITY_PROTECTED)
		);
		
		// constructor
		$type = $model->getPhpName() == 'Event' ? 'Model' : $model->getPhpName();
		$class->setMethod(PhpMethod::create('__construct')
			->addParameter(PhpParameter::create($modelVariableName)->setType($type))
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
				'class' => $model->getPhpName()
			]))
			->setDescription('Deletes a ' . $model->getPhpName() . ' with the given id')
			->setType('PayloadInterface')
		);
	}
	
	protected function generateToOneRelationshipSet(PhpTrait $trait, Relationship $relationship) {
		$model = $relationship->getModel();
		$name = $relationship->getRelatedName();
		$trait->setMethod(PhpMethod::create('set' . $name . 'Id')
			->setDescription(str_replace('{foreign}', $relationship->getRelatedName(), 'Sets the {foreign} id'))
			->addParameter(PhpParameter::create('id'))
			->addParameter(PhpParameter::create('relatedId'))
			->setBody($this->twig->render('to-one-set.twig', [
				'class' => $model->getPhpName(),
				'local' => $relationship->getForeignKey()->getLocalColumn()->getPhpName(),
				'const' => strtoupper(NameUtils::toSnakeCase($relationship->getRelatedName()))
			]))
			->setType('PayloadInterface')
		);
	}
	
	protected function generateToManyRelationshipAdd(PhpTrait $trait, Relationship $relationship) {
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\NotValid');
		
		$model = $relationship->getModel();
		$foreign = $relationship->getForeign();
		$trait->addUseStatement($foreign->getNamespace() . '\\' . $foreign->getPhpName() . 'Query');
		$trait->setMethod(PhpMethod::create('add' . $relationship->getRelatedPluralName())
			->setDescription('Adds ' . $relationship->getRelatedPluralName() . ' to ' . $model->getPhpName())
			->addParameter(PhpParameter::create('id'))
			->addParameter(PhpParameter::create('data'))
			->setBody($this->twig->render('to-many-add.twig', [
				'class' => $model->getPhpName(),			
				'related' => $relationship->getRelatedName(),
				'foreign_class' => $foreign->getPhpName(),
				'const' => strtoupper(NameUtils::toSnakeCase($relationship->getRelatedPluralName()))
			]))
			->setType('PayloadInterface')
		);
	}
	
	protected function generateToManyRelationshipRemove(PhpTrait $trait, Relationship $relationship) {
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\NotValid');
		
		$model = $relationship->getModel();
		$foreign = $relationship->getForeign();
		$trait->addUseStatement($foreign->getNamespace() . '\\' . $foreign->getPhpName() . 'Query');
		$trait->setMethod(PhpMethod::create('remove' . $relationship->getRelatedPluralName())
			->setDescription('Removes ' . $relationship->getRelatedPluralName() . ' from ' . $model->getPhpName())
			->addParameter(PhpParameter::create('id'))
			->addParameter(PhpParameter::create('data'))
			->setBody($this->twig->render('to-many-remove.twig', [
				'class' => $model->getPhpName(),
				'related' => $relationship->getRelatedName(),
				'foreign_class' => $foreign->getPhpName(),
				'const' => strtoupper(NameUtils::toSnakeCase($relationship->getRelatedPluralName()))
			]))
			->setType('PayloadInterface')
		);
	}
	
	protected function generateOneToManyRelationshipUpdate(PhpTrait $trait, OneToManyRelationship $relationship) {
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\NotValid');
	
		$model = $relationship->getModel();
		$foreign = $relationship->getForeign();
		$trait->addUseStatement($foreign->getNamespace() . '\\' . $foreign->getPhpName() . 'Query');
		$trait->setMethod(PhpMethod::create('update' . $relationship->getRelatedPluralName())
			->setDescription('Updates ' . $relationship->getRelatedPluralName() . ' on ' . $model->getPhpName())
			->addParameter(PhpParameter::create('id'))
			->addParameter(PhpParameter::create('data'))
			->setBody($this->twig->render('one-to-many-update.twig', [
				'class' => $model->getPhpName(),
				'related' => $relationship->getRelatedName(),
				'reverse_related' => $relationship->getReverseRelatedName(),
				'foreign_class' => $foreign->getPhpName(),
				'const' => strtoupper(NameUtils::toSnakeCase($relationship->getRelatedPluralName()))
			]))
			->setType('PayloadInterface')
		);
	}
	
	protected function generateManyToManyRelationshipUpdate(PhpTrait $trait, ManyToManyRelationship $relationship) {
		$trait->addUseStatement('keeko\\framework\\domain\\payload\\NotValid');
	
		$model = $relationship->getModel();
		$foreign = $relationship->getForeign();
		$middle = $relationship->getMiddle();
		$trait->addUseStatement($middle->getNamespace() . '\\' . $middle->getPhpName() . 'Query');
		$trait->addUseStatement($foreign->getNamespace() . '\\' . $foreign->getPhpName() . 'Query');
		$trait->setMethod(PhpMethod::create('update' . $relationship->getRelatedPluralName())
			->setDescription('Updates ' . $relationship->getRelatedPluralName() . ' on ' . $model->getPhpName())
			->addParameter(PhpParameter::create('id'))
			->addParameter(PhpParameter::create('data'))
			->setBody($this->twig->render('many-to-many-update.twig', [
				'class' => $model->getPhpName(),
				'related' => $relationship->getRelatedName(),
				'reverse_related' => $relationship->getReverseRelatedName(),
				'foreign_class' => $foreign->getPhpName(),
				'middle_class' => $middle->getPhpName(),
				'const' => strtoupper(NameUtils::toSnakeCase($relationship->getRelatedPluralName()))
			]))
			->setType('PayloadInterface')
		);
	}
}