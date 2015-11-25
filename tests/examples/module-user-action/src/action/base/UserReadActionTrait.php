<?php
namespace keeko\user\action\base;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use keeko\core\model\User;
use keeko\core\model\UserQuery;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Base methods for keeko\user\action\UserReadAction
 * 
 * This code is automatically created. Modifications will probably be overwritten.
 * 
 * @author Tester
 */
trait UserReadActionTrait {

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

		// set response and go
		$this->response->setData($user);
		return $this->response->run($request);
	}

	/**
	 * @param OptionsResolverInterface $resolver
	 */
	public function setDefaultParams(OptionsResolverInterface $resolver) {
		$resolver->setRequired(['id']);
	}
}
