<?php
namespace keeko\tools\generator\domain;

use keeko\tools\generator\AbstractCodeGenerator;

abstract class AbstractDomainGenerator extends AbstractCodeGenerator {
	
	protected function getTemplateFolder() {
		return 'domain';
	}
}