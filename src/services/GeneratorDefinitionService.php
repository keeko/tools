<?php
namespace keeko\tools\services;

use Propel\Generator\Model\Table;

class GeneratorDefinitionService extends AbstractService {

	/**
	 * Returns code for hydrating a propel model
	 *
	 * @param string $modelName
	 * @return string
	 */
	public function getWriteFields($modelName) {
		$generatorDefinition = $this->project->getGeneratorDefinition();
		$filter = $generatorDefinition->getWriteFilter($modelName);
		$model = $this->modelService->getModel($modelName);
		$computed = $this->getComputedFields($model);
		$filter = array_merge($filter, $computed);

		$fields = [];
		$cols = $model->getColumns();
		foreach ($cols as $col) {
			$prop = $col->getName();

			if (!in_array($prop, $filter)) {
				$fields[] = $prop;
			}
		}

		return $fields;
	}

	/**
	 * Returns the fields for a model
	 *
	 * @param string $modelName
	 * @return array
	 */
	public function getReadFields($modelName) {
		$generatorDefinition = $this->project->getGeneratorDefinition();
		$model = $this->modelService->getModel($modelName);
// 		$computed = $this->getComputedFields($model);
		$filter = $generatorDefinition->getReadFilter($modelName);
// 		$filter = array_merge($filter, $computed);

		$fields = [];
		$cols = $model->getColumns();
		foreach ($cols as $col) {
			$prop = $col->getName();

			if (!in_array($prop, $filter) && !$col->isForeignKey() && !$col->isPrimaryKey()) {
				$fields[] = $prop;
			}
		}

		return $fields;
	}

	/**
	 * Returns computed model fields
	 *
	 * @param Table $table
	 * @return array<string>
	 */
	public function getComputedFields(Table $table) {
		$fields = [];

		// iterate over behaviors to get their respective columns
		foreach ($table->getBehaviors() as $behavior) {
			switch ($behavior->getName()) {
				case 'timestampable':
					$fields[] = $behavior->getParameter('create_column');
					$fields[] = $behavior->getParameter('update_column');
					break;

				case 'aggregate_column':
					$fields[] = $behavior->getParameter('name');
					break;
			}
		}

		return $fields;
	}

	/**
	 * Returns all attributes that aren't written onto a model (because manually filter or computed)
	 *
	 * @param Table $model
	 * @return array
	 */
	public function getWriteFilter(Table $model) {
		$modelName = $model->getOriginCommonName();
		$generatorDefinition = $this->project->getGeneratorDefinition();
		$filter = $generatorDefinition->getWriteFilter($modelName);
		$computed = $this->getComputedFields($model);
		return array_merge($filter, $computed);
	}
}