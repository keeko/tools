<?php
namespace keeko\tools\generator\responder;

use gossi\codegen\model\AbstractPhpStruct;
use keeko\framework\schema\ActionSchema;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;

trait PayloadGeneratorTrait {
	
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
}