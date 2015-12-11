<?php
namespace keeko\user\response;

use keeko\core\action\AbstractResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Automatically generated JsonResponse for Deletes an user
 * 
 * @author Tester
 */
class UserDeleteJsonResponse extends AbstractResponse {

	use UserResponseTrait;

	/**
	 * Automatically generated run method
	 * 
	 * @param Request $request
	 * @param mixed $data
	 * @return Response
	 */
	public function run(Request $request, $data = null) {
		// return response
		return new JsonResponse($this->userToArray($data));
	}
}
