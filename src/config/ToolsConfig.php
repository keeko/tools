<?php
namespace keeko\tools\config;

class ToolsConfig {

	public function __construct() {}
	
	public function getTemplateRoot() {
		return __DIR__ . '/../../templates';
	}
}