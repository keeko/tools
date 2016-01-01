<?php
namespace keeko\user\action;

use keeko\core\package\AbstractAction;
use keeko\user\action\base\UserDeleteActionTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deletes an user
 * 
 * @author Tester
 */
class UserDeleteAction extends AbstractAction {

	use UserDeleteActionTrait;
}
