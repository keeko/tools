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
	
	protected function generatePayloadMethod($name, $body) {
		return PhpMethod::create($name)
			->addParameter(PhpParameter::create('request')
				->setType('Request')
			)
			->addParameter(PhpParameter::create('payload')
				->setType('PayloadInterface')
			)
			->setBody($body)
		;
	}
	
	protected function generateNotValid(PhpClass $class) {
		$class->addUseStatement('keeko\framework\exceptions\ValidationException');
		$notValid = $this->generatePayloadMethod('notValid', $this->twig->render('payload/notValid.twig'));
		$class->setMethod($notValid);
	}
	
	protected function generateNotFound(PhpClass $class) {
		$class->addUseStatement('Symfony\Component\Routing\Exception\ResourceNotFoundException');
		$notFound = $this->generatePayloadMethod('notFound', $this->twig->render('payload/notFound.twig'));
		$class->setMethod($notFound);
	}
	
	protected function getRelationshipIncludes(Table $model, $root = '', $processed = []) {
		if (in_array($model->getOriginCommonName(), $processed)) {
			return [];
		}
		
		$relationships = $this->modelService->getRelationships($model);
		$includes = [];
	
		foreach ($relationships['all'] as $rel) {
			$fk = $rel['fk'];
			$foreignModel = $fk->getForeignTable();
			$processed[] = $foreignModel->getOriginCommonName();
			
			$typeName = NameUtils::dasherize($fk->getForeignTable()->getOriginCommonName());
			if ($rel['type'] == 'many') {
				$typeName = NameUtils::pluralize($typeName);
			}
			$includes[] = (!empty($root) ? $root . '.' : '') . $typeName;
			$includes = array_merge($includes, $this->getRelationshipIncludes($foreignModel, $typeName, $processed));
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

		foreach ($relationships['all'] as $rel) {
			$fk = $rel['fk'];
			$foreignModel = $fk->getForeignTable();
			$processed[] = $foreignModel->getOriginCommonName();
			
			$typeName = NameUtils::dasherize($fk->getForeignTable()->getOriginCommonName());
			if ($rel['type'] == 'many') {
				$typeName = NameUtils::pluralize($typeName);
			}
			$name = (!empty($root) ? $root . '.' : '') . $typeName;
			
			$fields[$name] = $foreignModel;
			$fields = array_merge($fields, $this->getModelFields($foreignModel, $name, $processed));
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
