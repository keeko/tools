<?php
namespace keeko\tools\model;

use Propel\Generator\Model\CrossForeignKeys;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;

class ManyToManyRelationship extends Relationship {
	
	/** @var ForeignKey */
	protected $lk;
	
	/** @var CrossForeignKeys */
	protected $cfk;
	
	public function __construct(Table $model, CrossForeignKeys $cfk) {
		$this->model = $model;
		$this->cfk = $cfk;
		
		foreach ($cfk->getMiddleTable()->getForeignKeys() as $fk) {
			if ($fk->getForeignTable() != $model) {
				$this->fk = $fk;
			} else if ($fk->getForeignTable() == $model) {
				$this->lk = $fk;
			}
		}
		
		$this->foreign = $this->fk->getForeignTable();
	}
	
	/**
	 * Returns the type of this relationship
	 *
	 * @return string
	 */
	public function getType() {
		return self::MANY_TO_MANY;
	}
	
	/**
	 * Returns the middle table
	 * 
	 * @return Table
	 */
	public function getMiddle() {
		return $this->cfk->getMiddleTable();
	}
	
	/**
	 * Returns the cross foreign keys
	 * 
	 * @return CrossForeignKeys
	 */
	public function getCrossForeignKeys() {
		return $this->cfk;
	}
	
	/**
	 * Returns the local key
	 * 
	 * @return ForeignKey
	 */
	public function getLocalKey() {
		return $this->lk;
	}
	
	public function getRelatedName() {
		$relatedName = $this->lk->getRefPhpName();
		if (empty($relatedName)) {
			$relatedName = $this->foreign->getPhpName();
		}
		
		return $relatedName;
	}
	
	public function getReverseRelatedName() {
		$reverseRelatedName = $this->lk->getPhpName();
		if (empty($reverseRelatedName)) {
			$reverseRelatedName = $this->model->getPhpName();
		}
	
		return $reverseRelatedName;
	}

}
