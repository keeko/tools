<?php
namespace keeko\tools\generator\api;

use gossi\swagger\collections\Parameters;
use Propel\Generator\Model\Table;
use gossi\swagger\collections\Responses;

class ApiCreateOperationGenerator extends ApiCrudOperationGenerator {

	protected function generateParams(Parameters $params, Table $model) {
		$body = $params->getByName('body');
		$body->setName('body');
		$body->setIn('body');
		$body->setDescription(sprintf('The new %s', $model->getOriginCommonName()));
		$body->setRequired(true);
		$body->getSchema()->setRef('#/definitions/Writable' . $model->getPhpName());
	}

	protected function generateResponses(Responses $responses, Table $model) {
		$ok = $responses->get('201');
		$ok->setDescription(sprintf('%s created', $model->getOriginCommonName()));
	}
}