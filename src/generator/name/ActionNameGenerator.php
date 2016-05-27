<?php
namespace keeko\tools\generator\name;

use keeko\framework\utils\NameUtils;
use keeko\tools\model\Relationship;
use phootwork\lang\Text;
use Propel\Generator\Model\Table;

/**
 * Generates action names
 * 
 * @author gossi
 */
class ActionNameGenerator extends AbstractModelNameGenerator {
	
	//
	// Model generators
	//

	/**
	 * Generates the name for a list action
	 *
	 * @param Table $model
	 * @return string
	 */
	public function generateModelPaginate(Table $model) {
		return $model->getOriginCommonName() . '-paginate';
	}
	
	/**
	 * Generates the name for a create action
	 * 
	 * @param Table $model
	 * @return string
	 */
	public function generateModelCreate(Table $model) {
		return $model->getOriginCommonName() . '-create';
	}

	/**
	 * Generates the name for a read action
	 * 
	 * @param Table $model
	 * @return string
	 */
	public function generateModelRead(Table $model) {
		return $model->getOriginCommonName() . '-read';
	}

	/**
	 * Generates the name for an update action
	 * 
	 * @param Table $model
	 * @return string
	 */
	public function generateModelUpdate(Table $model) {
		return $model->getOriginCommonName() . '-update';
	}

	/**
	 * Generates the name for a delete action
	 * 
	 * @param Table $model
	 * @return string
	 */
	public function generateModelDelete(Table $model) {
		return $model->getOriginCommonName() . '-delete';
	}
	
	//
	// Relationship generators
	//

	/**
	 * Generates the name for a relationship read action
	 * 
	 * @param Relationship $relationship
	 * @return string
	 */
	public function generateRelationshipRead(Relationship $relationship) {
		return Text::create('{model}-to-{related}-relationship-read')->supplant([
			'{model}' => $relationship->getModel()->getOriginCommonName(),
			'{related}' => NameUtils::toSnakeCase($relationship->getRelatedTypeName())
		])->toString();
	}

	/**
	 * Generates the name for a relationship update action
	 * 
	 * @param Relationship $relationship
	 * @return string
	 */
	public function generateRelationshipUpdate(Relationship $relationship) {
		return Text::create('{model}-to-{related}-relationship-update')->supplant([
			'{model}' => $relationship->getModel()->getOriginCommonName(),
			'{related}' => NameUtils::toSnakeCase($relationship->getRelatedTypeName())
		])->toString();
	}

	/**
	 * Generates the name for a relationship add action
	 * 
	 * @param Relationship $relationship
	 * @return string
	 */
	public function generateRelationshipAdd(Relationship $relationship) {
		return Text::create('{model}-to-{related}-relationship-add')->supplant([
			'{model}' => $relationship->getModel()->getOriginCommonName(),
			'{related}' => NameUtils::toSnakeCase($relationship->getRelatedTypeName())
		])->toString();
	}

	/**
	 * Generates the name for a relationship remove action
	 * 
	 * @param Relationship $relationship
	 * @return string
	 */
	public function generateRelationshipRemove(Relationship $relationship) {
		return Text::create('{model}-to-{related}-relationship-remove')->supplant([
			'{model}' => $relationship->getModel()->getOriginCommonName(),
			'{related}' => NameUtils::toSnakeCase($relationship->getRelatedTypeName())
		])->toString();
	}
	
	public function parseName($actionName) {
		if (strpos($actionName, 'relationship') !== false) {
			return $this->parseRelationship($actionName);
		} else {
			return $this->parseModel($actionName);
		}
	}
	
	public function parseModel($actionName) {
		return [
			'modelName' => substr($actionName, 0, strpos($actionName, '-')),
			'type' => substr($actionName, strrpos($actionName, '-') + 1)
		];
	}
	
	public function parseRelationship($actionName) {
		$matches = [];
		preg_match('/([a-z_]+)-to-([a-z_]+)-relationship.*/i', $actionName, $matches);

		return [
			'prefix' => substr($actionName, 0, strpos($actionName, 'relationship') + 12),
			'type' => substr($actionName, strrpos($actionName, '-') + 1),
			'modelName' => $matches[1],
			'relatedName' => $matches[2]
		];
	}
}