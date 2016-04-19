<?php
namespace keeko\tools\model;

use Propel\Generator\Model\Table;
use Propel\Generator\Model\CrossForeignKeys;
use Propel\Generator\Model\ForeignKey;
use keeko\framework\utils\NameUtils;

class ManyRelationship extends Relationship {
	
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
		return 'many';
	}
	
	/**
	 * @return Table
	 */
	public function getMiddle() {
		return $this->cfk->getMiddleTable();
	}
	
	/**
	 * @return CrossForeignKeys
	 */
	public function getCrossForeignKeys() {
		return $this->cfk;
	}
	
	/**
	 * @return ForeignKey
	 */
	public function getLocalKey() {
		return $this->lk;
	}
	
	public function getRelatedName() {
		$relatedName = $this->lk->getRefPhpName();
		if ($relatedName === null) {
			$relatedName = $this->foreign->getPhpName();
		}
		
		return $relatedName;
	}

	public function getRelatedTypeName() {
		return NameUtils::dasherize(NameUtils::pluralize($this->getRelatedName()));
	}
	
}