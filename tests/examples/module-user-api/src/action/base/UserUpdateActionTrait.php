<?php
namespace keeko\user\action\base;

use keeko\core\package\AbstractAction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use keeko\core\model\User;
use keeko\core\model\UserQuery;
use keeko\core\exceptions\ValidationException;
use keeko\core\utils\HydrateUtils;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Base methods for keeko\user\action\UserUpdateAction
 * 
 * This code is automatically created. Modifications will probably be overwritten.
 * 
 * @author Tester
 */
trait UserUpdateActionTrait {

	/**
	 * @param OptionsResolver $resolver
	 */
	public function configureParams(OptionsResolver $resolver) {
		$resolver->setRequired(['id']);
	}

	/**
	 * Automatically generated run method
	 * 
	 * @param Request $request
	 * @return Response
	 */
	public function run(Request $request) {
		// read
		$id = $this->getParam('id');
		$user = UserQuery::create()->findOneById($id);

		// check existence
		if ($user === null) {
			throw new ResourceNotFoundException('user not found.');
		}

		// hydrate
		$data = json_decode($request->getContent(), true);
		$user = HydrateUtils::hydrate($data, $user, ['id', 'login_name', 'password' => function($v) {
			return password_hash($v, PASSWORD_BCRYPT);
		}, 'given_name', 'family_name', 'display_name', 'email', 'birthday', 'sex']);

		// validate
		if (!$user->validate()) {
			throw new ValidationException($user->getValidationFailures());
		} else {
			return $this->response->run($request, $user);
		}
	}
}
