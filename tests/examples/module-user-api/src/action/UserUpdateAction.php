<?php
namespace keeko\user\action;

use keeko\core\package\AbstractAction;
use keeko\user\action\base\UserUpdateActionTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Updates an user
 * 
 * @author Tester
 */
class UserUpdateAction extends AbstractAction {

	use UserUpdateActionTrait;
}
