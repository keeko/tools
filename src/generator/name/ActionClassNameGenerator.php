<?php
namespace keeko\tools\generator\name;

use keeko\tools\model\Relationship;
use Propel\Generator\Model\Table;

/**
 * Generates action class names
 * 
 * @author gossi
 */
class ActionClassNameGenerator extends AbstractModelNameGenerator {
	
	/**
	 * Returns the namespace for the model actions
	 * 
	 * @return string
	 */
	private function getModelActionNamespace() {
		return $this->service->getFactory()->getNamespaceGenerator()->getModelActionNamespace();
	}
	
	/**
	 * Returns the namespace for relationship actions
	 *
	 * @return string
	 */
	private function getRelationshipActionNamespace() {
		return $this->service->getFactory()->getNamespaceGenerator()->getRelationshipActionNamespace();
	}
	
	//
	// Model generators
	//
	
	/**
	 * Generates the class name for a list action
	 *
	 * @param Table $model
	 * @return string
	 */
	public function generateModelPaginate(Table $model) {
		return sprintf('%s\\%sPaginateAction', $this->getModelActionNamespace(), $model->getPhpName());
	}

	/**
	 * Generates the class name for a create action
	 * 
	 * @param Table $model
	 * @return string
	 */
	public function generateModelCreate(Table $model) {
		return sprintf('%s\\%sCreateAction', $this->getModelActionNamespace(), $model->getPhpName());
	}

	/**
	 * Generates the class name for a read action
	 * 
	 * @param Table $model
	 * @return string
	 */
	public function generateModelRead(Table $model) {
		return sprintf('%s\\%sReadAction', $this->getModelActionNamespace(), $model->getPhpName());
	}

	/**
	 * Generates the class name for an update action
	 * 
	 * @param Table $model
	 * @return string
	 */
	public function generateModelUpdate(Table $model) {
		return sprintf('%s\\%sUpdateAction', $this->getModelActionNamespace(), $model->getPhpName());
	}

	/**
	 * Generates the class name for a delete action
	 * 
	 * @param Table $model
	 * @return string
	 */
	public function generateModelDelete(Table $model) {
		return sprintf('%s\\%sDeleteAction', $this->getModelActionNamespace(), $model->getPhpName());
	}
	
	//
	// Relationship generators
	//

	/**
	 * Generates the class name for a relationship read action
	 * 
	 * @param Relationship $relationship
	 * @return string
	 */
	public function generateRelationshipRead(Relationship $relationship) {
		$model = $relationship->getModel();
		return sprintf('%s\\%s%sReadAction', $this->getRelationshipActionNamespace(), 
			$model->getPhpName(), $relationship->getRelatedName());
	}

	/**
	 * Generates the class name for a relationship update action
	 * 
	 * @param Relationship $relationship
	 * @return string
	 */
	public function generateRelationshipUpdate(Relationship $relationship) {
		$model = $relationship->getModel();
		return sprintf('%s\\%s%sUpdateAction', $this->getRelationshipActionNamespace(), 
			$model->getPhpName(), $relationship->getRelatedName());
	}

	/**
	 * Generates the class name for a relationship add action
	 * 
	 * @param Relationship $relationship
	 * @return string
	 */
	public function generateRelationshipAdd(Relationship $relationship) {
		$model = $relationship->getModel();
		return sprintf('%s\\%s%sAddAction', $this->getRelationshipActionNamespace(), 
			$model->getPhpName(), $relationship->getRelatedName());
	}

	/**
	 * Generates the class name for a relationship remove action
	 * 
	 * @param Relationship $relationship
	 * @return string
	 */
	public function generateRelationshipRemove(Relationship $relationship) {
		$model = $relationship->getModel();
		return sprintf('%s\\%s%sRemoveAction', $this->getRelationshipActionNamespace(), 
			$model->getPhpName(), $relationship->getRelatedName());
	}
}