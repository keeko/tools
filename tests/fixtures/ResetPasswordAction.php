<?php
namespace keeko\user\action;

use keeko\core\package\AbstractAction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resets the password
 * 
 * @author Tester
 */
class ResetPasswordAction extends AbstractAction {

	/**
	 * Automatically generated run method
	 * 
	 * @param Request $request
	 * @return Response
	 */
	public function run(Request $request) {
		return $this->response->run($request);
	}
}
