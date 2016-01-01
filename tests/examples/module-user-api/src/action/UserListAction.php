<?php
namespace keeko\user\action;

use keeko\core\package\AbstractAction;
use keeko\user\action\base\UserListActionTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * List all users
 * 
 * @author Tester
 */
class UserListAction extends AbstractAction {

	use UserListActionTrait;
}
