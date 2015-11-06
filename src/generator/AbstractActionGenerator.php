<?php
namespace keeko\tools\generator;

use keeko\tools\generator\AbstractCodeGenerator;

class AbstractActionGenerator extends AbstractCodeGenerator {

	protected function getTemplateFolder() {
		return 'actions';
	}
}