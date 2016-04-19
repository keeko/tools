<?php
namespace keeko\tools\generator\responder;

use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use keeko\framework\utils\NameUtils;
use Propel\Generator\Model\Table;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\AbstractPhpStruct;
use keeko\framework\schema\ActionSchema;

class AbstractPayloadJsonResponderGenerator extends AbstractJsonResponderGenerator {
	
	protected function ensureUseStatements(AbstractPhpStruct $struct) {
		parent::ensureUseStatements($struct);
		$struct->removeUseStatement('keeko\\framework\\domain\\payload\\PayloadInterface');
		$struct->removeUseStatement('keeko\\framework\\foundation\\AbstractResponder');
		$struct->addUseStatement('keeko\\framework\\foundation\\AbstractPayloadResponder');
	}
	
	protected function generateStruct(ActionSchema $action, $format) {
		$class = parent::generateStruct($action, $format);
		$class->setParentClassName('AbstractPayloadResponder');
		
		return $class;
	}
	
	protected function generateGetPayloadMethods(PhpClass $class, $body = '') {
		$class->setMethod(PhpMethod::create('getPayloadMethods')
			->setVisibility(PhpMethod::VISIBILITY_PROTECTED)
			->setBody($body)
		);
	}
	
	protected function generatePayloadMethod($name, $body, $type = 'PayloadInterface') {
		return PhpMethod::create($name)
			->addParameter(PhpParameter::create('request')
				->setType('Request')
			)
			->addParameter(PhpParameter::create('payload')
				->setType($type)
			)
			->setBody($body)
		;
	}
	
	protected function generateNotValid(PhpClass $class) {
		$class->addUseStatement('keeko\\framework\\domain\\payload\\NotValid');
		$class->addUseStatement('keeko\framework\exceptions\ValidationException');
		$notValid = $this->generatePayloadMethod('notValid', $this->twig->render('payload/notValid.twig'),
			'NotValid');
		$class->setMethod($notValid);
	}
	
	protected function generateNotFound(PhpClass $class) {
		$class->addUseStatement('keeko\\framework\\domain\\payload\\NotFound');
		$class->addUseStatement('Symfony\Component\Routing\Exception\ResourceNotFoundException');
		$notFound = $this->generatePayloadMethod('notFound', $this->twig->render('payload/notFound.twig'),
			'NotFound');
		$class->setMethod($notFound);
	}
	
	protected function getRelationshipIncludes(Table $model, $root = '', $processed = []) {
		if (in_array($model->getOriginCommonName(), $processed)) {
			return [];
		}
		
		$relationships = $this->modelService->getRelationships($model);
		$includes = [];
	
		foreach ($relationships->getAll() as $rel) {
			$foreign = $rel->getForeign();
			$processed[] = $foreign->getOriginCommonName();
			
			$typeName = $rel->getRelatedTypeName();
			$includes[] = (!empty($root) ? $root . '.' : '') . $typeName;
			$includes = array_merge($includes, $this->getRelationshipIncludes($foreign, $typeName, $processed));
		}
	
		return $includes;
	}
	
	protected function getModelFields(Table $model, $root = '', $processed = []) {
		if (in_array($model->getOriginCommonName(), $processed)) {
			return [];
		}
		
		$typeName = NameUtils::dasherize($model->getOriginCommonName());
		$relationships = $this->modelService->getRelationships($model);
		$fields = [$typeName => $model];

		foreach ($relationships->getAll() as $rel) {
			$foreign = $rel->getForeign();
			$processed[] = $foreign->getOriginCommonName();
			
			$typeName = $rel->getRelatedTypeName();
			$name = (!empty($root) ? $root . '.' : '') . $typeName;
			
			$fields[$name] = $foreign;
			$fields = array_merge($fields, $this->getModelFields($foreign, $name, $processed));
		}
		
		return $fields;
	}
	
	protected function getFieldsCode(array $fields) {
		$code = '';
		foreach ($fields as $typeName => $field) {
			$code .= sprintf("\t'%s' => %s::getSerializer()->getFields(),\n", $typeName, $field->getPhpName());
		}
		
		if (strlen($code) > 0) {
			$code = substr($code, 0, -2);
		}
		
		return sprintf("[\n%s\n]", $code);
	}
	
}
