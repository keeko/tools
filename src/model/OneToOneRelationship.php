<?php
namespace keeko\tools\model;

use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;

class OneToOneRelationship extends Relationship {
	
	public function __construct(Table $model, Table $foreign, ForeignKey $fk) {
		$this->model = $model;
		$this->foreign = $foreign;
		$this->fk = $fk;
	}
	
	public function getType() {
		return self::ONE_TO_ONE;
	}
	
	public function getRelatedName() {
		$relatedName = $this->fk->getPhpName();
		if (empty($relatedName)) {
			$relatedName = $this->foreign->getPhpName();
		}
	
		return $relatedName;
	}
	
	public function getReverseRelatedName() {
		$reverseRelatedName = $this->fk->getRefPhpName();
		if (empty($reverseRelatedName)) {
			$reverseRelatedName = $this->model->getPhpName();
		}
	
		return $reverseRelatedName;
	}
}