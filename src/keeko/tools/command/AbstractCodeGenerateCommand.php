<?php
namespace keeko\tools\command;

use Symfony\Component\Console\Command\Command;

abstract class AbstractCodeGenerateCommand extends Command {

	private $json = null;
	protected $templateRoot; 

	public function __construct($name = null) {
		parent::__construct($name);
		
		$this->templateRoot = __DIR__ . '/../../../../templates';
	}
	
	/**
	 * Returns the keeko node from the composer.json extra
	 */
	protected function getKeeko() {
		$json = $this->getJsonContents();
		
		if (!array_key_exists('extra', $json) && !array_key_exists('keeko', $json['extra'])) {
			throw new \RuntimeException('no extra.keeko node found in composer.json');
		}
		
		return $json['extra']['keeko'];
	}
	
	protected function getKeekoModule() {
		$keeko = $this->getKeeko();
		
		if (!array_key_exists('module', $keeko)) {
			throw new \RuntimeException('no extra.keeko.module node found in composer.json');
		}
		
		return $keeko['module'];
	}
	

	protected function getKeekoModuleActions() {
		$module = $this->getKeekoModule();
	
		if (!array_key_exists('actions', $module)) {
			throw new \RuntimeException('no extra.keeko.module.actions node found in composer.json');
		}
	
		return $module['actions'];
	}
	
	protected function getJsonContents() {
		if ($this->json === null) {
			$jsonFile = getcwd() . '/composer.json';
			
			if (!file_exists($jsonFile)) {
				throw new \RuntimeException('composer.json not found');
			}
			
			$this->json = json_decode(file_get_contents($jsonFile), true);
		}
		
		return $this->json;
	}
}