<?php
namespace keeko\user\response;

use keeko\core\action\AbstractResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Automatically generated JsonResponse for Reads an user
 * 
 * @author Tester
 */
class UserReadJsonResponse extends AbstractResponse {

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
