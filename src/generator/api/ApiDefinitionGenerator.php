<?php
namespace keeko\tools\generator\api;

use gossi\swagger\collections\Definitions;
use keeko\framework\utils\NameUtils;
use keeko\tools\generator\AbstractGenerator;
use Propel\Generator\Model\Table;
use keeko\tools\model\Relationship;

class ApiDefinitionGenerator extends AbstractGenerator {

	private $needsPagedMeta = false;
	private $needsResourceIdentifier = false;

	public function generate(Definitions $definitions, Table $model) {
		// stop if model is excluded
		$generatorDefinition = $this->project->getGeneratorDefinition();
		if ($generatorDefinition->getExcludedApi()->contains($model->getOriginCommonName())) {
			return;
		}

		$this->logger->notice('Generating Definition for: ' . $model->getOriginCommonName());
		$modelObjectName = $model->getPhpName();

		// paged model
		$this->needsPagedMeta = true;
		$pagedModel = 'Paged' . NameUtils::pluralize($modelObjectName);
		$paged = $definitions->get($pagedModel)->setType('object')->getProperties();
		$paged->get('data')
			->setType('array')
			->getItems()->setRef('#/definitions/' . $modelObjectName);
		$paged->get('meta')->setRef('#/definitions/PagedMeta');

		// writable model
		$writable = $definitions->get('Writable' . $modelObjectName)->setType('object')->getProperties();
		$this->generateModelProperties($writable, $model, true);

		// readable model
		$readable = $definitions->get($modelObjectName)->setType('object')->getProperties();
		$this->generateModelProperties($readable, $model, false);
	}

	protected function generateModelProperties(Definitions $props, Table $model, $write = false) {
		// links
		if (!$write) {
			$links = $props->get('links')->setType('object')->getProperties();
			$links->get('self')->setType('string');
		}

		// data
		$data = $this->generateResourceData($props);

		// attributes
		$attrs = $data->get('attributes');
		$attrs->setType('object');
		$this->generateModelAttributes($attrs->getProperties(), $model, $write);

		// relationships
		if ($this->hasRelationships($model)) {
			$relationships = $data->get('relationships')->setType('object')->getProperties();
			$this->generateModelRelationships($relationships, $model, $write);
		}
	}

	protected function generateModelAttributes(Definitions $props, Table $model, $write = false) {
		$modelName = $model->getOriginCommonName();
		$filter = $write
			? $this->project->getGeneratorDefinition()->getWriteFilter($modelName)
			: $this->project->getGeneratorDefinition()->getReadFilter($modelName);

		if ($write) {
			$filter = array_merge($filter, $this->generatorDefinitionService->getComputedFields($model));
		}

		// no id, already in identifier
		$filter[] = 'id';
		$types = ['int' => 'integer'];

		foreach ($model->getColumns() as $col) {
			$prop = $col->getName();

			if (!in_array($prop, $filter)) {
				$type = $col->getPhpType();
				if (isset($types[$type])) {
					$type = $types[$type];
				}
				$props->get($prop)->setType($type);
			}
		}

		return $props;
	}

	protected function hasRelationships(Table $model) {
		$relationships = $this->modelService->getRelationships($model);
		return $relationships->size() > 0;
	}

	protected function generateModelRelationships(Definitions $props, Table $model, $write = false) {
		$relationships = $this->modelService->getRelationships($model);

		foreach ($relationships->getAll() as $relationship) {
			// one-to-one
			if ($relationship->getType() == Relationship::ONE_TO_ONE) {
				$typeName = $relationship->getRelatedTypeName();
				$rel = $props->get($typeName)->setType('object')->getProperties();

				// links
				if (!$write) {
					$links = $rel->get('links')->setType('object')->getProperties();
					$links->get('self')->setType('string');
				}

				// data
				$this->generateResourceData($rel);
			}

			// ?-to-many
			else {
				$typeName = $relationship->getRelatedPluralTypeName();
				$rel = $props->get($typeName)->setType('object')->getProperties();

				// links
				if (!$write) {
					$links = $rel->get('links')->setType('object')->getProperties();
					$links->get('self')->setType('string');
				}

				// data
				$this->needsResourceIdentifier = true;
				$rel->get('data')
					->setType('array')
					->getItems()->setRef('#/definitions/ResourceIdentifier');
			}
		}
	}

	protected function generateResourceData(Definitions $props) {
		$data = $props->get('data')->setType('object')->getProperties();
		$props->get('id')->setType('string');
		$props->get('type')->setType('string');
		return $data;
	}

	public function needsPagedMeta() {
		return $this->needsPagedMeta;
	}

	public function needsResourceIdentifier() {
		return $this->needsResourceIdentifier;
	}
}