<?php
namespace keeko\tools\model;

use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;

class OneToOneRelationship extends Relationship {

	/** @var OneToOneRelationship */
	protected $reverse;

	/** @var OneToOneRelationship */
	private $defined;

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

	/**
	 * Return the reverse relationship
	 *
	 * @return Relationship
	 */
	public function getReverseRelationship() {
		return $this->reverse;
	}

	public function setReverseRelationship(OneToOneRelationship $relationship) {
		if ($this->reverse != $relationship) {
			$this->reverse = $relationship;
			$this->reverse->setDefinedRelationship($this);
		}
	}

	public function getDefinedRelationship() {
		return $this->defined;
	}

	public function setDefinedRelationship(OneToOneRelationship $relationship) {
		if ($this->defined != $relationship) {
			$this->defined = $relationship;
			$this->defined->setReverseRelationship($this);
		}
	}
}