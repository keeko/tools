<?php
namespace keeko\user\response;

use keeko\core\package\AbstractResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Automatically generated JsonResponse for List all users
 * 
 * @author Tester
 * @author gossi
 */
class UserListJsonResponse extends AbstractResponse {

	use UserResponseTrait;

	/**
	 * Automatically generated run method
	 * 
	 * @param Request $request
	 * @param mixed $data
	 * @return JsonResponse
	 */
	public function run(Request $request, $data = null) {
		$out = [];

		// build model
		$out['users'] = [];
		foreach ($data as $user) {
			$out['users'][] = $this->userToArray($user);
		}

		// meta
		$out['meta'] = [
			'total' => $data->getNbResults(),
			'first' => $data->getFirstPage(),
			'next' => $data->getNextPage(),
			'previous' => $data->getPreviousPage(),
			'last' => $data->getLastPage()
		];

		// return response
		return new JsonResponse($out);
	}
}
