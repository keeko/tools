<?php
namespace keeko\tools\helpers;

use keeko\framework\schema\ActionSchema;
use keeko\framework\utils\NameUtils;
use keeko\tools\services\CommandService;

trait ActionCommandHelperTrait {
	
	/**
	 * @return CommandService
	 */
	abstract protected function getService();
	
	/**
	 *
	 * @param string $actionName
	 * @return ActionSchema
	 */
	private function getAction($actionName) {
		$packageService = $this->getService()->getPackageService();
		$action = $packageService->getAction($actionName);
		if ($action === null) {
			$action = new ActionSchema($actionName);
			$module = $packageService->getModule();
			$module->addAction($action);
		}
		return $action;
	}
	
	private function getAcl(ActionSchema $action) {
		$input = $this->getService()->getIOService()->getInput();
		$acls = [];
		$acl = $input->getOption('acl');
		if ($acl !== null && count($acl) > 0) {
			if (!is_array($acl)) {
				$acl = [$acl];
			}
			foreach ($acl as $group) {
				if (strpos($group, ',') !== false) {
					$groups = explode(',', $group);
					foreach ($groups as $g) {
						$acls[] = trim($g);
					}
				} else {
					$acls[] = $group;
				}
			}
				
			return $acls;
		}
	
		// read default from package
		if (!$action->getAcl()->isEmpty()) {
			return $action->getAcl()->toArray();
		}
	
		return $acls;
	}
	
	private function guessClassname($name) {
		$factory = $this->getService()->getFactory();
		$namespace = $factory->getNamespaceGenerator()->getActionNamespace();
		return $namespace . '\\' . NameUtils::toStudlyCase($name) . 'Action';
	}
}