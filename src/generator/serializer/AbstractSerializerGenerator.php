<?php
namespace keeko\tools\generator\serializer;

use keeko\tools\generator\AbstractCodeGenerator;

class AbstractSerializerGenerator extends AbstractCodeGenerator {
	
	protected function getTemplateFolder() {
		return 'serializer';
	}

}