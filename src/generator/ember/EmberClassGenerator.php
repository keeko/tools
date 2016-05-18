<?php
namespace keeko\tools\generator\ember;

use phootwork\collection\ArrayList;
use phootwork\collection\Map;
use gossi\codegen\utils\Writer;

class EmberClassGenerator {
	
	private $name;
	private $parent;
	private $imports;
	private $properties;
	
	public function __construct($parent = null) {
		$this->imports = new ArrayList();
		$this->properties = new Map();
		
		if ($parent === null) {
			$parent = 'Ember.Object';
			$this->addImport('Ember', 'ember');
		}
		$this->parent = $parent;
	}
	
	public function addImport($import, $from) {
		$this->imports->add(['import' => $import, 'from' => $from]);
	}
	
	public function setProperty($property, $value) {
		$this->properties->set($property, $value);
	}
	
	public function generate() {
		$writer = new Writer();
		
		// add imports first
		foreach ($this->imports as $import) {
			$writer->writeln(sprintf('import %s from \'%s\';', $import['import'], $import['from']));
		}
		
		if ($this->imports->size() > 0) {
			$writer->writeln('');
		}
		
		// class signature
		$writer->writeln(sprintf('export default %s.extend({', $this->parent));
		$writer->indent();
		
		// properties
		$propper = new Writer();
		foreach ($this->properties as $prop => $value) {
			$propper->writeln(sprintf('%s: %s,', $prop, $value));
		}
		$writer->writeln(rtrim($propper->getContent(), ", \n"));

		// class foot
		$writer->outdent();
		$writer->writeln('});');
		
		return $writer->getContent();
	}
}