<?php
namespace keeko\user\action\base;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use keeko\core\model\UserQuery;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Base methods for keeko\user\action\UserReadAction
 * 
 * This code is automatically created. Modifications will probably be overwritten.
 * 
 * @author Tester
 * @author gossi
 */
trait UserReadActionTrait {

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

		// run response
		return $this->response->run($request, $user);
	}
}
