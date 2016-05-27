<?php
namespace keeko\tools\generator\name;

use keeko\framework\utils\NameUtils;
use keeko\tools\model\Relationship;
use phootwork\lang\Text;
use Propel\Generator\Model\Table;

/**
 * Generates action titles
 *
 * @author gossi
 */
class ActionTitleGenerator extends AbstractModelNameGenerator {
	
	//
	// Model generators
	//
	
	/**
	 * Generates the title for a list action
	 *
	 * @param Table $model
	 * @return string
	 */
	public function generateModelPaginate(Table $model) {
		return 'Paginates ' . NameUtils::pluralize($model->getOriginCommonName());
	}
	
	/**
	 * Generates the title for a create action
	 *
	 * @param Table $model
	 * @return string
	 */
	public function generateModelCreate(Table $model) {
		$title = $model->getOriginCommonName();
		return 'Creates ' . (in_array($title[0], ['a', 'e', 'i', 'o', 'u']) ? 'an' : 'a') . ' ' . $title;
	}
	
	/**
	 * Generates the title for a read action
	 *
	 * @param Table $model
	 * @return string
	 */
	public function generateModelRead(Table $model) {
		$title = $model->getOriginCommonName();
		return 'Reads ' . (in_array($title[0], ['a', 'e', 'i', 'o', 'u']) ? 'an' : 'a') . ' ' . $title;
	}

	/**
	 * Generates the title for an update action
	 *
	 * @param Table $model
	 * @return string
	 */
	public function generateModelUpdate(Table $model) {
		$title = $model->getOriginCommonName();
		return 'Updates ' . (in_array($title[0], ['a', 'e', 'i', 'o', 'u']) ? 'an' : 'a') . ' ' . $title;
	}
	
	/**
	 * Generates the title for a delete action
	 *
	 * @param Table $model
	 * @return string
	 */
	public function generateModelDelete(Table $model) {
		$name = $model->getOriginCommonName();
		return 'Deletes ' . (in_array($name[0], ['a', 'e', 'i', 'o', 'u']) ? 'an' : 'a') . ' ' . $name;
	}
	
	//
	// Relationship generators
	//
	
	/**
	 * Generates a relationship read action title
	 * 
	 * @param Relationship $relationship
	 * @return string
	 */
	public function generateRelationshipRead(Relationship $relationship) {
		return Text::create('Reads the relationship of {model} to {related}')->supplant([
			'{model}' => $relationship->getModel()->getOriginCommonName(),
			'{related}' => NameUtils::toSnakeCase($relationship->getRelatedName())
		])->toString();
	}
	
	/**
	 * Generates a relationship update action title
	 * 
	 * @param Relationship $relationship
	 * @return string
	 */
	public function generateRelationshipUpdate(Relationship $relationship) {
		return Text::create('Updates the relationship of {model} to {related}')->supplant([
			'{model}' => $relationship->getModel()->getOriginCommonName(),
			'{related}' => NameUtils::toSnakeCase($relationship->getRelatedName())
		])->toString();
	}
	
	/**
	 * Generates a relationship add action title
	 * 
	 * @param Relationship $relationship
	 * @return string
	 */
	public function generateRelationshipAdd(Relationship $relationship) {
		return Text::create('Adds {related} as relationship to {model}')->supplant([
			'{model}' => $relationship->getModel()->getOriginCommonName(),
			'{related}' => NameUtils::toSnakeCase($relationship->getRelatedName())
		])->toString();
	}
	
	/**
	 * Generates a relationship remove action title
	 * 
	 * @param Relationship $relationship
	 * @return string
	 */
	public function generateRelationshipRemove(Relationship $relationship) {
		return Text::create('Removes {related} as relationship to {model}')->supplant([
			'{model}' => $relationship->getModel()->getOriginCommonName(),
			'{related}' => NameUtils::toSnakeCase($relationship->getRelatedName())
		])->toString();
	}

}