<?php
namespace keeko\tools\model;

class ReverseOneToOneRelationship extends OneToOneRelationship {

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