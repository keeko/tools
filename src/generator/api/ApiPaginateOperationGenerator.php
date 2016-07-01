<?php
namespace keeko\tools\generator\api;

use gossi\swagger\collections\Parameters;
use Propel\Generator\Model\Table;
use gossi\swagger\collections\Responses;
use keeko\framework\utils\NameUtils;

class ApiPaginateOperationGenerator extends ApiCrudOperationGenerator {

	protected function generateParams(Parameters $params, Table $model) {

	}

	protected function generateResponses(Responses $responses, Table $model) {
		$ok = $responses->get('200');
		$ok->setDescription(sprintf('Array of %s', NameUtils::pluralize($model->getOriginCommonName())));
		$ok->getSchema()->setRef('#/definitions/' . 'Paged' . NameUtils::pluralize($model->getPhpName()));
	}
}