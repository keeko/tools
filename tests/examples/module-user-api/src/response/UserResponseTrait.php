<?php
namespace keeko\user\response;

use keeko\core\model\User;
use keeko\core\utils\FilterUtils;
use Propel\Runtime\Map\TableMap;

/**
 * Automatically generated common response methods for user
 * 
 * @author Tester
 * @author gossi
 */
trait UserResponseTrait {

	/**
	 * Automatically generated method, will be overridden
	 * 
	 * @param array $user
	 */
	protected function filter(array $user) {
		return FilterUtils::blacklistFilter($user, ['password', 'password_recover_code', 'password_recover_time']);
	}

	/**
	 * Automatically generated method, will be overridden
	 * 
	 * @param User $user
	 */
	protected function userToArray(User $user) {
		return $this->filter($user->toArray(TableMap::TYPE_CAMELNAME));
	}
}
