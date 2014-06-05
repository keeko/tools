<?php

namespace keeko\tools\builder;

use TwigGenerator\Builder\BaseBuilder;

class ActionBuilder extends BaseBuilder {
	
	public function __construct($variables = []) {
		parent::__construct();
		$this->setVariables($variables);
		$this->setTemplateName('struct.twig');
	}
}