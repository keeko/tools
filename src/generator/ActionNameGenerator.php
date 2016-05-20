<?php
namespace keeko\tools\generator;

use Propel\Generator\Model\Table;
use keeko\tools\model\Relationship;
use keeko\framework\utils\NameUtils;

/**
 * Generates action names
 * 
 * @author gossi
 */
class ActionNameGenerator {
	
	/**
	 * Generates an action name for a given model and a type
	 * 
	 * @param string $type
	 * @param Table $model
	 * @return string|null Returns the name or null if the type is unknown
	 */
	public static function generate($type, Table $model) {
		switch ($type) {
			case 'create':
				return self::generateCreate($model);
			
			case 'read':
				return self::generateRead($model);
				
			case 'list':
				return self::generateList($model);
				
			case 'update':
				return self::generateUpdate($model);
				
			case 'delete':
				return self::generateDelete($model);
		}
		
		return null;
	}

	/**
	 * Generates the name for a create action
	 * 
	 * @param Table $model
	 * @return string
	 */
	public static function generateCreate(Table $model) {
		return $model->getOriginCommonName() . '-create';
	}

	/**
	 * Generates the name for a read action
	 * 
	 * @param Table $model
	 * @return string
	 */
	public static function generateRead(Table $model) {
		return $model->getOriginCommonName() . '-read';
	}

	/**
	 * Generates the name for a list action
	 * 
	 * @param Table $model
	 * @return string
	 */
	public static function generateList(Table $model) {
		return $model->getOriginCommonName() . '-list';
	}

	/**
	 * Generates the name for an update action
	 * 
	 * @param Table $model
	 * @return string
	 */
	public static function generateUpdate(Table $model) {
		return $model->getOriginCommonName() . '-update';
	}

	/**
	 * Generates the name for a delete action
	 * 
	 * @param Table $model
	 * @return string
	 */
	public static function generateDelete(Table $model) {
		return $model->getOriginCommonName() . '-delete';
	}
	
	/**
	 * Generates the name for a relationship action with a given relationship and type
	 * 
	 * @param string $type
	 * @param Relationship $relationship
	 * @return string|null Returns the name or null if the type is unknown
	 */
	public static function generateRelationship($type, Relationship $relationship) {
		switch ($type) {
			case 'read':
				return self::generateRelationshipRead($relationship);
				
			case 'update':
				return self::generateRelationshipUpdate($relationship);
				
			case 'add':
				return self::generateRelationshipAdd($relationship);
				
			case 'remove':
				return self::generateRelationshipRemove($relationship);
		}
		
		return null;
	}

	/**
	 * Generates the name for a relationship read action
	 * 
	 * @param Relationship $relationship
	 * @return string
	 */
	public static function generateRelationshipRead(Relationship $relationship) {
		$model = $relationship->getModel();
		$related = NameUtils::toSnakeCase($relationship->getRelatedTypeName());
		return sprintf('%s-to-%s-relationship-read', 
			$model->getOriginCommonName(), $related);
	}

	/**
	 * Generates the name for a relationship update action
	 * 
	 * @param Relationship $relationship
	 * @return string
	 */
	public static function generateRelationshipUpdate(Relationship $relationship) {
		$model = $relationship->getModel();
		$related = NameUtils::toSnakeCase($relationship->getRelatedTypeName());
		return sprintf('%s-to-%s-relationship-update',
			$model->getOriginCommonName(), $related);
	}

	/**
	 * Generates the name for a relationship add action
	 * 
	 * @param Relationship $relationship
	 * @return string
	 */
	public static function generateRelationshipAdd(Relationship $relationship) {
		$model = $relationship->getModel();
		$related = NameUtils::toSnakeCase($relationship->getRelatedTypeName());
		return sprintf('%s-to-%s-relationship-add',
			$model->getOriginCommonName(), $related);
	}

	/**
	 * Generates the name for a relationship remove action
	 * 
	 * @param Relationship $relationship
	 * @return string
	 */
	public static function generateRelationshipRemove(Relationship $relationship) {
		$model = $relationship->getModel();
		$related = NameUtils::toSnakeCase($relationship->getRelatedTypeName());
		return sprintf('%s-to-%s-relationship-remove',
			$model->getOriginCommonName(), $related);
	}
}