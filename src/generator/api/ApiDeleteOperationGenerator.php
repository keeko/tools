<?php
namespace keeko\tools\generator\api;

use gossi\swagger\collections\Parameters;
use Propel\Generator\Model\Table;
use gossi\swagger\collections\Responses;

class ApiDeleteOperationGenerator extends ApiCrudOperationGenerator {

	protected function generateParams(Parameters $params, Table $model) {
		$this->generateIdParam($params, $model);
	}

	protected function generateResponses(Responses $responses, Table $model) {
		$ok = $responses->get('204');
		$ok->setDescription(sprintf('%s deleted', $model->getOriginCommonName()));

		$this->generateInvalidResponse($responses);
		$this->generateNotFoundResponse($responses, $model);
	}
}