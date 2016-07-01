<?php
namespace keeko\tools\generator\api;

use gossi\swagger\collections\Parameters;
use Propel\Generator\Model\Table;
use gossi\swagger\collections\Responses;

class ApiReadOperationGenerator extends ApiCrudOperationGenerator {

	protected function generateParams(Parameters $params, Table $model) {
		$this->generateIdParam($params, $model);
	}

	protected function generateResponses(Responses $responses, Table $model) {
		$ok = $responses->get('200');
		$ok->setDescription(sprintf('gets the %s', $model->getOriginCommonName()));
		$ok->getSchema()->setRef('#/definitions/' . $model->getPhpName());

		$this->generateInvalidResponse($responses);
		$this->generateNotFoundResponse($responses, $model);
	}
}