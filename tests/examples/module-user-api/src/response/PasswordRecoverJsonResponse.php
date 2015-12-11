<?php
namespace keeko\user\response;

use keeko\core\action\AbstractResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Automatically generated JsonResponse for Recover Password
 * 
 * @author Tester
 */
class PasswordRecoverJsonResponse extends AbstractResponse {

	/**
	 * Automatically generated run method
	 * 
	 * @param Request $request
	 * @param mixed $data
	 * @return Response
	 */
	public function run(Request $request, $data = null) {
		return new JsonResponse();
	}
}
