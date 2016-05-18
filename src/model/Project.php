<?php
namespace keeko\tools\model;

use phootwork\file\File;
use keeko\framework\schema\PackageSchema;

class Project {
	
	private $root;
	private $package;
	
	public function __construct($workdir) {
		$this->root = $workdir;
	}
	
	/**
	 * @return string
	 */
	public function getRootPath() {
		return $this->root;
	}
	
	public function getComposerFileName() {
		return $this->root . '/composer.json'; 
	}
	
	public function hasComposerFile() {
		$file = new File($this->getComposerFileName());
		return $file->exists();
	}

	public function getApiFileName() {
		return $this->root . '/api.json';
	}
	
	public function getSchemaFileName() {
		return $this->root . '/res/database/schema.xml';
	}
	
	public function hasApiFile() {
		$file = new File($this->getApiFileName());
		return $file->exists();
	}
	
	public function getCodegenFileName() {
		return $this->root . '/codegen.json';
	}
	
	public function hasCodegenFile() {
		$file = new File($this->getCodegenFileName());
		return $file->exists();
	}
	
	public function hasSchemaFile() {
		$file = new File($this->getSchemaFileName());
		return $file->exists();
	}
	
	public function getPackage() {
		if ($this->package === null) {
			if ($this->hasComposerFile()) {
				$this->package = PackageSchema::fromFile($this->getComposerFileName());
			}
		}
		return $this->package;
	}
}
