<?php
namespace keeko\tools\model;

class OneToManyRelationship extends OneToOneRelationship {

	public function getType() {
		return self::ONE_TO_MANY;
	}

	public function getRelatedName() {
		$relatedName = $this->fk->getRefPhpName();
		if (empty($relatedName)) {
			$relatedName = $this->foreign->getPhpName();
		}

		return $relatedName;
	}
	
	public function getReverseRelatedName() {
		$reverseRelatedName = $this->fk->getPhpName();
		if (empty($reverseRelatedName)) {
			$reverseRelatedName = $this->model->getPhpName();
		}
		
		return $reverseRelatedName;
	}
}