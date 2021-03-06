<?php
namespace keeko\tools\generator\serializer\base;

use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpTrait;
use keeko\framework\utils\NameUtils;
use keeko\tools\generator\serializer\AbstractSerializerGenerator;
use keeko\tools\model\Relationship;
use Propel\Generator\Model\Table;
use gossi\codegen\model\PhpProperty;

class ModelSerializerTraitGenerator extends AbstractSerializerGenerator {

	/**
	 *
	 * @param Table $model
	 * @return PhpTrait
	 */
	public function generate(Table $model) {
		$ns = $this->packageService->getNamespace();
		$fqcn = sprintf('%s\\serializer\\base\\%sSerializerTrait', $ns, $model->getPhpName());
		$trait = new PhpTrait($fqcn);

		$this->generateIdentifyingMethods($trait, $model);
		$this->generateAttributeMethods($trait, $model);
		$this->generateRelationshipMethods($trait, $model);
		$this->generateTypeInferencerAccess($trait);

		return $trait;
	}

	protected function generateIdentifyingMethods(PhpTrait $trait, Table $model) {
		$package = $this->packageService->getPackage();
		$type = sprintf('%s/%s', $package->getKeeko()->getModule()->getSlug(), NameUtils::dasherize($model->getOriginCommonName()));

		$trait->setMethod(PhpMethod::create('getId')
			->addParameter(PhpParameter::create('model'))
			->setBody($this->twig->render('getId.twig'))
			->setType('string')
		);

		$trait->setMethod(PhpMethod::create('getType')
			->addParameter(PhpParameter::create('model'))
			->setBody($this->twig->render('getType.twig', [
				'type' => $type
			]))
			->setType('string')
		);
	}

	protected function generateAttributeMethods(PhpTrait $trait, Table $model) {
		$readFields = $this->generatorDefinitionService->getReadFields($model->getOriginCommonName());
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

		$trait->setMethod(PhpMethod::create('getAttributes')
			->addParameter(PhpParameter::create('model'))
			->addParameter(PhpParameter::create('fields')->setType('array')->setDefaultValue(null))
			->setBody($this->twig->render('getAttributes.twig', [
				'attrs' => $attrs
			]))
		);

		$trait->setMethod(PhpMethod::create('getSortFields')
			->setBody($this->twig->render('getFields.twig', [
				'fields' => $this->codeService->arrayToCode(array_map(function ($field) {
					return NameUtils::dasherize($field);
				}, $readFields))
			]))
		);

		$readFields = $this->generatorDefinitionService->getReadFields($model->getOriginCommonName());
		$trait->setMethod(PhpMethod::create('getFields')
			->setBody($this->twig->render('getFields.twig', [
				'fields' => $this->codeService->arrayToCode(array_map(function ($field) {
					return NameUtils::dasherize($field);
				}, $readFields))
			]))
		);
	}

	protected function generateRelationshipMethods(PhpTrait $trait, Table $model) {
// 		if ($model->isReadOnly()) {
// 			return;
// 		}

		$fields = [];
		$methods = [];
		$plural = [];
		$relationships = $this->modelService->getRelationships($model);
		$methodNameGenerator = $this->factory->getRelationshipMethodNameGenerator();

		// add self link for relationships if there are any
		if ($relationships->size() > 0) {
			$trait->addUseStatement('Tobscure\\JsonApi\\Relationship');
			$trait->setMethod(PhpMethod::create('addRelationshipSelfLink')
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

		// iterate all relationships
		foreach ($relationships->getAll() as $rel) {
			// one-to-one
			if ($rel->getType() == Relationship::ONE_TO_ONE) {
				$foreign = $rel->getForeign();
				$relatedName = $rel->getRelatedName();
				$typeName = $rel->getRelatedTypeName();
				$method = NameUtils::toCamelCase($relatedName);
				$fields[$typeName] = $foreign->getPhpName() . '::getSerializer()->getType(null)';
				$trait->addUseStatement($foreign->getNamespace() . '\\' . $foreign->getPhpName());
				$trait->addUseStatement('Tobscure\\JsonApi\\Resource');

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
				$trait->addUseStatement($foreign->getNamespace() . '\\' . $foreign->getPhpName());
				$trait->addUseStatement('Tobscure\\JsonApi\\Collection');

				// read
				$body = $this->twig->render('to-many-read.twig', [
					'class' => $foreign->getPhpName(),
					'related' => $typeName,
					'related_type' => $rel->getRelatedTypeName()
				]);

				// method name for collection
				$methods[$typeName] = $methodNameGenerator->generateMethodName($rel);
				$plural[$typeName] = $methodNameGenerator->generatePluralMethodName($rel);
// 				if ($rel->getType() == Relationship::MANY_TO_MANY
// 						&& $rel->getForeign() == $rel->getModel()) {
// 					$lk = $rel->getLocalKey();
// 					$methods[$typeName] = $foreign->getPhpName() . 'RelatedBy' . $lk->getLocalColumn()->getPhpName();
// 					$plural[$typeName] = NameUtils::pluralize($foreign->getPhpName()) . 'RelatedBy' . $lk->getLocalColumn()->getPhpName();
// 				}
			}

			// set read method on class
			$trait->setMethod(PhpMethod::create($method)
				->addParameter(PhpParameter::create('model'))
				->setBody($body)
				->setType('Relationship')
			);

			// add reverse many-to-many
			if ($rel->getType() == Relationship::MANY_TO_MANY && $rel->isReflexive()) {
				$foreign = $rel->getForeign();
				$typeName = $rel->getReverseRelatedPluralTypeName();
				$method = NameUtils::toCamelCase($rel->getReverseRelatedPluralName());
				$fields[$typeName] = $foreign->getPhpName() . '::getSerializer()->getType(null)';
				$trait->addUseStatement($foreign->getNamespace() . '\\' . $foreign->getPhpName());
				$trait->addUseStatement('Tobscure\\JsonApi\\Collection');

				// read
				$body = $this->twig->render('to-many-read.twig', [
					'class' => $foreign->getPhpName(),
					'related' => $typeName,
					'related_type' => $rel->getReverseRelatedTypeName()
				]);

				$methods[$typeName] = $methodNameGenerator->generateReverseMethodName($rel);
				$plural[$typeName] = $methodNameGenerator->generateReversePluralMethodName($rel);

				// set read method on class
				$trait->setMethod(PhpMethod::create($method)
					->addParameter(PhpParameter::create('model'))
					->setBody($body)
					->setType('Relationship')
				);
			}
		}

		// method: getRelationships() : array
		$trait->setMethod(PhpMethod::create('getRelationships')
			->setBody($this->twig->render('getRelationships.twig', [
				'fields' => $fields
			]))
		);

		// method: getCollectionMethodName($relatedName) : string
		$trait->setProperty(PhpProperty::create('methodNames')
			->setExpression($this->codeService->mapToCode($methods))
			->setVisibility(PhpProperty::VISIBILITY_PRIVATE)
		);
		$trait->setMethod(PhpMethod::create('getCollectionMethodName')
			->addParameter(PhpParameter::create('relatedName'))
			->setBody($this->twig->render('getCollectionMethodName.twig'))
			->setVisibility(PhpMethod::VISIBILITY_PROTECTED)
		);

		// method: getCollectionMethodPluralName($relatedName) : string
		$trait->setProperty(PhpProperty::create('methodPluralNames')
			->setExpression($this->codeService->mapToCode($plural))
			->setVisibility(PhpProperty::VISIBILITY_PRIVATE)
		);
		$trait->setMethod(PhpMethod::create('getCollectionMethodPluralName')
			->addParameter(PhpParameter::create('relatedName'))
			->setBody($this->twig->render('getCollectionMethodPluralName.twig'))
			->setVisibility(PhpMethod::VISIBILITY_PROTECTED)
		);
	}

	protected function generateTypeInferencerAccess(PhpTrait $trait) {
		$namespace = $this->factory->getNamespaceGenerator()->getSerializerNamespace();
		$trait->addUseStatement($namespace . '\\TypeInferencer');
		$trait->setMethod(PhpMethod::create('getTypeInferencer')
			->setVisibility(PhpMethod::VISIBILITY_PROTECTED)
			->setBody($this->twig->render('getTypeInferencer.twig'))
		);
	}
}
