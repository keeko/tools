<?php
namespace keeko\tools\model;

use Propel\Generator\Model\CrossForeignKeys;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;
use phootwork\collection\Set;
use phootwork\collection\Map;

class ManyToManyRelationship extends Relationship {

	/** @var ForeignKey */
	protected $lk;

	/** @var CrossForeignKeys */
	protected $cfk;

	public function __construct(Table $model, CrossForeignKeys $cfk) {
		$this->model = $model;
		$this->cfk = $cfk;

		$fkTables = new Map();
		foreach ($cfk->getMiddleTable()->getForeignKeys() as $fk) {
			if (!$fkTables->has($fk->getForeignTableCommonName())) {
				$fkTables->set($fk->getForeignTableCommonName(), 0);
			}
			$fkTables->set($fk->getForeignTableCommonName(), $fkTables->get($fk->getForeignTableCommonName()) + 1);
		}

		$idColumns = [];
		if ($fkTables->get($model->getCommonName()) > 1) {
			$name = '';
			$splits = explode('_', $cfk->getMiddleTable()->getOriginCommonName());
			foreach ($splits as $split) {
				if (empty($name)) {
					$name = $split;
				} else {
					$name .= '_' . $split;
					$idColumns []= $split . '_id';
				}

				$idColumns []= $name . '_id';
			}
		}

		foreach ($cfk->getMiddleTable()->getForeignKeys() as $fk) {
			// looks like a many-to-many parent + child relationship
			if ($fkTables->get($model->getCommonName()) > 1) {
				if (in_array($fk->getLocalColumnName(), $idColumns)) {
					$this->lk = $fk;
				} else {
					$this->fk = $fk;
				}
			}

			// normal many-to-many relationship
			else {
				if ($fk->getForeignTable() != $model) {
					$this->fk = $fk;
				} else if ($fk->getForeignTable() == $model) {
					$this->lk = $fk;
				}
			}
		}

		if ($this->fk === null) {
			echo $cfk->getMiddleTable()->getOriginCommonName() . "\n";
			echo $cfk->getTable()->getOriginCommonName() . "\n";
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
		$reverseRelatedName = $this->fk->getRefPhpName();
		if (empty($reverseRelatedName)) {
			$reverseRelatedName = $this->model->getPhpName();
		}

		return $reverseRelatedName;
	}
}
