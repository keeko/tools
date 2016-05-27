<?php
namespace keeko\tools\generator\name;

use Propel\Generator\Model\Table;
use keeko\tools\model\Relationship;
use keeko\tools\generator\Types;

abstract class AbstractModelNameGenerator extends AbstractNameGenerator {
	
	/**
	 * Generates a name with a given type and based on the object type
	 *
	 * @param string $type
	 * @param Table|Relationship $object
	 * @return string|null Returns the name or null if the type or object is unknown
	 */
	public function generate($type, $object) {
		if ($object instanceof Table) {
			return $this->generateModel($type, $object);
		} else if ($object instanceof Relationship) {
			return $this->generateRelationship($type, $object);
		}
	
		return null;
	}
	
	/**
	 * Generates a name for a model action with a given type
	 *
	 * @param string $type
	 * @param Table $model
	 * @return string|null Returns the name or null if the type is unknown
	 */
	public function generateModel($type, Table $model) {
		switch ($type) {
			case Types::PAGINATE:
				return $this->generateModelPaginate($model);
			
			case Types::CREATE:
				return $this->generateModelCreate($model);
			
			case Types::READ:
				return $this->generateModelRead($model);
				
			case Types::UPDATE:
				return $this->generateModelUpdate($model);
				
			case Types::DELETE:
				return $this->generateModelDelete($model);
		}
	
		return null;
	}
	
	/**
	 * Generates a name for a model create type
	 *
	 * @param Table $model
	 * @return string
	 */
	abstract public function generateModelCreate(Table $model);
	
	/**
	 * Generates a name for a model read type
	 *
	 * @param Table $model
	 * @return string
	 */
	abstract public function generateModelRead(Table $model);
	
	/**
	 * Generates a name for a model paginate type
	 *
	 * @param Table $model
	 * @return string
	 */
	abstract public function generateModelPaginate(Table $model);
	
	/**
	 * Generates a name for a model update type
	 *
	 * @param Table $model
	 * @return string
	 */
	abstract public function generateModelUpdate(Table $model);
	
	/**
	 * Generates a name for a model delete type
	 *
	 * @param Table $model
	 * @return string
	 */
	abstract public function generateModelDelete(Table $model);
	
	/**
	 * Generates a name for a relationship action with a given type
	 *
	 * @param string $type
	 * @param Relationship $relationship
	 * @return string|null Returns the name or null if the type is unknown
	 */
	public function generateRelationship($type, Relationship $relationship) {
		switch ($type) {
			case Types::READ:
				return $this->generateRelationshipRead($relationship);
				
			case Types::UPDATE:
				return $this->generateRelationshipUpdate($relationship);
				
			case Types::ADD:
				return $this->generateRelationshipAdd($relationship);
				
			case Types::REMOVE:
				return $this->generateRelationshipRemove($relationship);
		}
	
		return null;
	}
	
	/**
	 * Generates a name for a relationship read type
	 *
	 * @param Relationship $relationship
	 * @return string
	 */
	abstract public function generateRelationshipRead(Relationship $relationship);
	
	/**
	 * Generates a name for a relationship update type
	 *
	 * @param Relationship $relationship
	 * @return string
	 */
	abstract public function generateRelationshipUpdate(Relationship $relationship);
	
	/**
	 * Generates a name for a relationship add type
	 *
	 * @param Relationship $relationship
	 * @return string
	 */
	abstract public function generateRelationshipAdd(Relationship $relationship);
	
	/**
	 * Generates a name for a relationship remove type
	 *
	 * @param Relationship $relationship
	 * @return string
	 */
	abstract public function generateRelationshipRemove(Relationship $relationship);

}