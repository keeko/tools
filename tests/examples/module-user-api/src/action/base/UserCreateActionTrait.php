<?php
namespace keeko\user\action\base;

use keeko\core\package\AbstractAction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use keeko\core\model\User;
use keeko\core\exceptions\ValidationException;
use keeko\core\utils\HydrateUtils;

/**
 * Base methods for keeko\user\action\UserCreateAction
 * 
 * This code is automatically created. Modifications will probably be overwritten.
 * 
 * @author Tester
 */
trait UserCreateActionTrait {

	/**
	 * Automatically generated run method
	 * 
	 * @param Request $request
	 * @return Response
	 */
	public function run(Request $request) {
		$data = json_decode($request->getContent(), true);

		// hydrate
		$user = HydrateUtils::hydrate($data, new User(), ['id', 'login_name', 'password' => function($v) {
			return password_hash($v, PASSWORD_BCRYPT);
		}, 'given_name', 'family_name', 'display_name', 'email', 'birthday', 'sex']);

		// validate
		if (!$user->validate()) {
			throw new ValidationException($user->getValidationFailures());
		} else {
			$user->save();
			return $this->response->run($request, $user);
		}
	}
}
