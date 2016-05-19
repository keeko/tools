<?php
namespace keeko\tools\model;

use phootwork\file\File;
use keeko\framework\schema\PackageSchema;
use keeko\framework\schema\CodegenSchema;

class Project {
	
	/** @var string */
	private $root;
	
	/** @var PackageSchema */
	private $package;
	
	/** @var CodegenSchema */
	private $codegen;
	
	public function __construct($workdir) {
		$this->root = $workdir;
	}
	
	/**
	 * @return string
	 */
	public function getRootPath() {
		return $this->root;
	}
	
	/**
	 * @return string
	 */
	public function getComposerFileName() {
		return $this->root . '/composer.json'; 
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function hasComposerFile() {
		$file = new File($this->getComposerFileName());
		return $file->exists();
	}

	/**
	 * @return string
	 */
	public function getApiFileName() {
		return $this->root . '/api.json';
	}
	
	/**
	 * @return boolean
	 */
	public function hasApiFile() {
		$file = new File($this->getApiFileName());
		return $file->exists();
	}
	
	/**
	 * @return string
	 */
	public function getSchemaFileName() {
		return $this->root . '/res/database/schema.xml';
	}
	
	/**
	 * @return boolean
	 */
	public function hasSchemaFile() {
		$file = new File($this->getSchemaFileName());
		return $file->exists();
	}
	
	/**
	 * @return string
	 */
	public function getCodegenFileName() {
		return $this->root . '/codegen.json';
	}
	
	/**
	 * @return boolean
	 */
	public function hasCodegenFile() {
		$file = new File($this->getCodegenFileName());
		return $file->exists();
	}

	/**
	 * Returns the package from 'composer.json' or creates a blank one, if composer.json
	 * doesn't exist.
	 * 
	 * @return PackageSchema
	 */
	public function getPackage() {
		if ($this->package === null) {
			$this->package = $this->hasComposerFile()
				? PackageSchema::fromFile($this->getComposerFileName())
				: new PackageSchema();
		}
		return $this->package;
	}
	
	/**
	 * Returns a codegen schema from 'codegen.json' or creates a blank one, if 'codegen.json'
	 * doesn't exist.
	 *
	 * @throws JsonEmptyException
	 * @throws \RuntimeException
	 * @return CodegenSchema
	 */
	public function getCodegen() {
		if ($this->codegen === null) {
			$this->codegen = $this->hasCodegenFile()
				? CodegenSchema::fromFile($this->getCodegenFileName())
				: new CodegenSchema();
		}
	
		return $this->codegen;
	}
}
