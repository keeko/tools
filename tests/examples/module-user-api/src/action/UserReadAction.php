<?php
namespace keeko\user\action;

use keeko\core\package\AbstractAction;
use keeko\user\action\base\UserReadActionTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reads an user
 * 
 * @author Tester
 */
class UserReadAction extends AbstractAction {

	use UserReadActionTrait;
}
