<?php
namespace keeko\tools\model;

use Propel\Generator\Model\Table;
use Propel\Generator\Model\ForeignKey;
use keeko\framework\utils\NameUtils;

class Relationship {
	
	/** @var Table */
	protected $model;
	
	/** @var Table */
	protected $foreign;
	
	/** @var ForeignKey */
	protected $fk;
	
	public function __construct(Table $model, Table $foreign, ForeignKey $fk) {
		$this->model = $model;
		$this->foreign = $foreign;
		$this->fk = $fk;
	}
	
	/**
	 * Returns the type of this relationship
	 * 
	 * @return string
	 */
	public function getType() {
		return 'one';
	}

	/**
	 *
	 * @return Table
	 */
	public function getModel() {
		return $this->model;
	}

	/**
	 *
	 * @return Table
	 */
	public function getForeign() {
		return $this->foreign;
	}

	/**
	 *
	 * @return ForeignKey
	 */
	public function getForeignKey() {
		return $this->fk;
	}
	
	/**
	 * Returns the related name in studly case
	 * 
	 * @return string
	 */
	public function getRelatedName() {
		$relatedName = $this->fk->getPhpName();
		if ($relatedName === null) {
			$relatedName = $this->foreign->getPhpName();
		}
		
		return $relatedName;
	}
	
	/**
	 * Returns the related name for using in api environment (slug, type-name, etc)
	 * 
	 * @return string
	 */
	public function getRelatedTypeName() {
		return NameUtils::dasherize($this->getRelatedName());
	}
}