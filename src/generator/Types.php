<?php
namespace keeko\tools\generator;

use Propel\Generator\Model\Table;
use keeko\tools\model\Relationship;

class Types {
	
	const PAGINATE = 'paginate';
	const CREATE = 'create';
	const READ = 'read';
	const UPDATE = 'update';
	const DELETE = 'delete';
	const ADD = 'add';
	const REMOVE = 'remove';
	
	private static $modelReadOnlyTypes = [self::PAGINATE, self::READ];
	private static $modelTypes = [self::PAGINATE, self::CREATE, self::READ, self::UPDATE, self::DELETE];
	
	private static $relationshipToOneTypes = [self::READ, self::UPDATE];
	private static $relationshipToManyTypes = [self::READ, self::UPDATE, self::ADD, self::REMOVE];
	
	public static function getModelTypes(Table $model) {
		return $model->isReadOnly() ? self::$modelReadOnlyTypes : self::$modelTypes;
	}
	
	public static function getRelationshipTypes(Relationship $relationship) {
		return $relationship->getType() == Relationship::ONE_TO_ONE
			? self::$relationshipToOneTypes : self::$relationshipToManyTypes;
	}
}