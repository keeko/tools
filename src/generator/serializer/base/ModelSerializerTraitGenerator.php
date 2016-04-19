<?php
namespace keeko\tools\generator\serializer\base;

use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpTrait;
use keeko\framework\utils\NameUtils;
use keeko\tools\generator\serializer\AbstractSerializerGenerator;
use Propel\Generator\Model\Table;
use keeko\tools\model\ManyRelationship;
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
		$type = sprintf('%s/%s', $package->getCleanName(), NameUtils::dasherize($model->getOriginCommonName()));
		
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
		$writeFields = $this->codegenService->getWriteFields($model->getOriginCommonName());
		$attrs = '';
		
		foreach ($writeFields as $field) {
			$col = $model->getColumn($field);
			$param = $col->isTemporalType() ? '\DateTime::ISO8601' : '';
			$attrs .= sprintf("\t'%s' => \$model->get%s(%s),\n", $field, $col->getPhpName(), $param);
		}
		
		if (count($field) > 0) {
			$attrs = substr($attrs, 0, -1);
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
				'fields' => $this->codegenService->arrayToCode($writeFields)
			]))
		);
		
		$readFields = $this->codegenService->getReadFields($model->getOriginCommonName());
		$class->setMethod(PhpMethod::create('getFields')
			->setBody($this->twig->render('getFields.twig', [
				'fields' => $this->codegenService->arrayToCode($readFields)
			]))
		);
	}
	
	protected function generateHydrateMethod(PhpTrait $trait, Table $model) {
		if ($model->isReadOnly()) {
			$body = $this->twig->render('hydrate-readonly.twig');
		} else {
			$trait->addUseStatement('keeko\\framework\\utils\\HydrateUtils');
			$modelName = $model->getOriginCommonName();
			$conversions = $this->codegenService->getCodegen()->getWriteConversion($modelName);
			$fields = $this->codegenService->getWriteFields($modelName);
			$code = '';
			
			foreach ($fields as $field) {
				$code .= "'$field'";
				if (isset($conversions[$field])) {
					$code .= ' => function($v) {' . "\n\t" . 'return ' . $conversions[$field] . ';' . "\n" . '}';
				}
		
				$code .= ', ';
			}
			
			if (strlen($code) > 0) {
				$code = substr($code, 0, -2);
			}
			
			$code = sprintf('[%s]', $code);
			$body = $this->twig->render('hydrate.twig', [
				'code' => $code
			]);
			
			$trait->setMethod(PhpMethod::create('hydrateRelationships')
				->addParameter(PhpParameter::create('model'))
				->addParameter(PhpParameter::create('data'))
				->setAbstract(true)
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
		
		$rels = [];
		$relationships = $this->modelService->getRelationships($model);
		
		if ($relationships->size() > 0) {
			$class->addUseStatement('Tobscure\\JsonApi\\Relationship');
		}
		
		foreach ($relationships->getAll() as $rel) {
			
			// to-many
			if ($rel instanceof ManyRelationship) {
				$foreign = $rel->getForeign();
				$relatedName = $rel->getRelatedName();
				
				$typeName = $rel->getRelatedTypeName();
				$method = NameUtils::toCamelCase($typeName);
				$rels[$typeName] = $foreign->getPhpName() . '::getSerializer()->getType(null)';
				$class->addUseStatement($foreign->getNamespace() . '\\' . $foreign->getPhpName());
				$class->addUseStatement('Tobscure\\JsonApi\\Collection');
				
				// read
				$body = $this->twig->render('to-many-read.twig', [
					'getter' => NameUtils::pluralize($relatedName),
					'class' => $relatedName,
					'related' => $typeName
				]);
			}
			
			// to-one
			else if ($rel instanceof Relationship) {
				$foreign = $rel->getForeign();
				$relatedName = $rel->getRelatedName();
				
				$typeName = $rel->getRelatedTypeName();
				$method = NameUtils::toCamelCase($typeName);
				$rels[$typeName] = $foreign->getPhpName() . '::getSerializer()->getType(null)';
				$class->addUseStatement($foreign->getNamespace() . '\\' . $foreign->getPhpName());
				$class->addUseStatement('Tobscure\\JsonApi\\Resource');
				
				// read
				$body = $this->twig->render('to-one-read.twig', [
					'ref' => $relatedName,
					'class' => $foreign->getPhpName(),
					'related' => $typeName
				]);
			}
			
			// needs to go down
			$class->setMethod(PhpMethod::create($method)
				->addParameter(PhpParameter::create('model'))
				->setBody($body)
				->setType('Relationship')
			);
		}
		
		$class->setMethod(PhpMethod::create('getRelationships')
			->setBody($this->twig->render('getRelationships.twig', [
				'fields' => $rels
			]))
		);
	}
}