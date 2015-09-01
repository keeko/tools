<?php
namespace keeko\tools\model;

use phootwork\file\File;
class Project {
	
	private $root;
	
	public function __construct($workdir) {
		$this->root = $workdir;
	}
	
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
}
