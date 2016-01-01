<?php
namespace keeko\user\action;

use keeko\core\package\AbstractAction;
use keeko\user\action\base\UserCreateActionTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Tester
 */
class UserCreateAction extends AbstractAction {

	use UserCreateActionTrait;
}
