<?php
namespace keeko\tools\generator\serializer\base;

use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpTrait;
use keeko\framework\utils\NameUtils;
use keeko\tools\generator\serializer\AbstractSerializerGenerator;
use Propel\Generator\Model\Table;
use keeko\tools\model\Relationship;

class ModelSerializerTraitGenerator extends AbstractSerializerGenerator {
	
	/**
	 * 
	 * @param Table $model
	 * @return PhpTrait
	 */
	public function generate(Table $model) {
		$ns = $this->packageService->getNamespace();
		$fqcn = sprintf('%s\\serializer\\base\\%sSerializerTrait', $ns, $model->getPhpName());
		$class = new PhpTrait($fqcn);
		
		$this->generateIdentifyingMethods($class, $model);
		$this->generateAttributeMethods($class, $model);
		$this->generateHydrateMethod($class, $model);
		$this->generateRelationshipMethods($class, $model);
		
		return $class;
	}
	
	protected function generateIdentifyingMethods(PhpTrait $class, Table $model) {
		$package = $this->packageService->getPackage();
		$type = sprintf('%s/%s', $package->getKeeko()->getModule()->getSlug(), NameUtils::dasherize($model->getOriginCommonName()));
		
		$class->setMethod(PhpMethod::create('getId')
			->addParameter(PhpParameter::create('model'))
			->setBody($this->twig->render('getId.twig'))
			->setType('string')
		);
		
		$class->setMethod(PhpMethod::create('getType')
			->addParameter(PhpParameter::create('model'))
			->setBody($this->twig->render('getType.twig', [
				'type' => $type
			]))
			->setType('string')
		);
	}
	
	protected function generateAttributeMethods(PhpTrait $class, Table $model) {
		$readFields = $this->codegenService->getReadFields($model->getOriginCommonName());
		$attrs = '';
		
		foreach ($readFields as $field) {
			$col = $model->getColumn($field);
			$param = $col->isTemporalType() ? '\DateTime::ISO8601' : '';
			$attrs .= sprintf("\t'%s' => \$model->get%s(%s),\n", 
				NameUtils::dasherize($field), $col->getPhpName(), $param
			);
		}
		
		if (count($field) > 0) {
			$attrs = substr($attrs, 0, -2);
		}
		
		$class->setMethod(PhpMethod::create('getAttributes')
			->addParameter(PhpParameter::create('model'))
			->addParameter(PhpParameter::create('fields')->setType('array')->setDefaultValue(null))
			->setBody($this->twig->render('getAttributes.twig', [
				'attrs' => $attrs
			]))
		);
		
		$class->setMethod(PhpMethod::create('getSortFields')
			->setBody($this->twig->render('getFields.twig', [
				'fields' => $this->codegenService->arrayToCode(array_map(function ($field) {
					return NameUtils::dasherize($field);
				}, $readFields))
			]))
		);
		
		$readFields = $this->codegenService->getReadFields($model->getOriginCommonName());
		$class->setMethod(PhpMethod::create('getFields')
			->setBody($this->twig->render('getFields.twig', [
				'fields' => $this->codegenService->arrayToCode(array_map(function ($field) {
					return NameUtils::dasherize($field);
				}, $readFields))
			]))
		);
	}

	protected function generateHydrateMethod(PhpTrait $trait, Table $model) {
		if ($model->isReadOnly()) {
			$body = $this->twig->render('hydrate-readonly.twig');
		} else {
			$trait->addUseStatement('keeko\\framework\\utils\\HydrateUtils');
			$modelName = $model->getOriginCommonName();
			$normalizer = $this->codegenService->getCodegen()->getNormalizer($modelName);
			$fields = $this->codegenService->getWriteFields($modelName);
			$code = '';
			
			foreach ($fields as $field) {
				$code .= sprintf("'%s'", NameUtils::dasherize($field));
				if ($normalizer->has($field)) {
					$code .= $this->twig->render('normalizer.twig', [
						'class' => $normalizer->get($field)
					]);
				}
		
				$code .= ', ';
			}
			
			if (strlen($code) > 0) {
				$code = substr($code, 0, -2);
			}
			
			$code = sprintf('[%s]', $code);
			$body = $this->twig->render('hydrate.twig', [
				'code' => $code,
				'normalizer' => $normalizer->size() > 0
			]);
			
			$trait->setMethod(PhpMethod::create('hydrateRelationships')
				->addParameter(PhpParameter::create('model'))
				->addParameter(PhpParameter::create('data'))
				->setAbstract(true)
				->setType('void')
				->setVisibility(PhpMethod::VISIBILITY_PROTECTED)
			);
		}
		
		$trait->setMethod(PhpMethod::create('hydrate')
			->addParameter(PhpParameter::create('model'))
			->addParameter(PhpParameter::create('data'))
			->setBody($body)
			->setType('mixed', 'The model')
		);
	}
	
	protected function generateRelationshipMethods(PhpTrait $class, Table $model) {
		if ($model->isReadOnly()) {
			return;
		}

		$fields = [];
		$relationships = $this->modelService->getRelationships($model);
		
		if ($relationships->size() > 0) {
			$class->addUseStatement('Tobscure\\JsonApi\\Relationship');
			$class->setMethod(PhpMethod::create('addRelationshipSelfLink')
				->addParameter(PhpParameter::create('relationship')
					->setType('Relationship')
				)
				->addParameter(PhpParameter::create('model')
					->setType('mixed')
				)
				->addParameter(PhpParameter::create('related')
					->setType('string')
				)
				->setAbstract(true)
				->setVisibility(PhpMethod::VISIBILITY_PROTECTED)
				->setType('Relationship')
			);
		}
		
		foreach ($relationships->getAll() as $rel) {
			// one-to-one
			if ($rel->getType() == Relationship::ONE_TO_ONE) {
				$foreign = $rel->getForeign();
				$relatedName = $rel->getRelatedName();
				$typeName = $rel->getRelatedTypeName();
				$method = NameUtils::toCamelCase($relatedName);
				$fields[$typeName] = $foreign->getPhpName() . '::getSerializer()->getType(null)';
				$class->addUseStatement($foreign->getNamespace() . '\\' . $foreign->getPhpName());
				$class->addUseStatement('Tobscure\\JsonApi\\Resource');
			
				// read
				$body = $this->twig->render('to-one-read.twig', [
					'class' => $foreign->getPhpName(),
					'related' => $relatedName,
					'related_type' => $typeName
				]);
			}
		
			// ?-to-many
			else {
				$foreign = $rel->getForeign();
				$typeName = $rel->getRelatedPluralTypeName();
				$method = NameUtils::toCamelCase($rel->getRelatedPluralName());
				$fields[$typeName] = $foreign->getPhpName() . '::getSerializer()->getType(null)';
				$class->addUseStatement($foreign->getNamespace() . '\\' . $foreign->getPhpName());
				$class->addUseStatement('Tobscure\\JsonApi\\Collection');
				
				// read
				$body = $this->twig->render('to-many-read.twig', [
					'class' => $foreign->getPhpName(),
					'related' => $rel->getRelatedPluralName(),
					'related_type' => $rel->getRelatedTypeName()
				]);
			}
			
			// set read method on class
			$class->setMethod(PhpMethod::create($method)
				->addParameter(PhpParameter::create('model'))
				->setBody($body)
				->setType('Relationship')
			);
		}
		
		$class->setMethod(PhpMethod::create('getRelationships')
			->setBody($this->twig->render('getRelationships.twig', [
				'fields' => $fields
			]))
		);
	}
}