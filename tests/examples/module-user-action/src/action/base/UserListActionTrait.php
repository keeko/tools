<?php
namespace keeko\user\action\base;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use keeko\core\model\UserQuery;

/**
 * Base methods for keeko\user\action\UserListAction
 * 
 * This code is automatically created. Modifications will probably be overwritten.
 * 
 * @author Tester
 * @author gossi
 */
trait UserListActionTrait {

	/**
	 * @param OptionsResolver $resolver
	 */
	public function configureParams(OptionsResolver $resolver) {
		$resolver->setDefaults([
			'page' => 1,
			'per_page' => 50,
		]);
	}

	/**
	 * Automatically generated run method
	 * 
	 * @param Request $request
	 * @return Response
	 */
	public function run(Request $request) {
		// read
		$page = $this->getParam('page');
		$perPage = $this->getParam('per_page');
		$user = UserQuery::create()->paginate($page, $perPage);

		// run response
		return $this->response->run($request, $user);
	}
}
