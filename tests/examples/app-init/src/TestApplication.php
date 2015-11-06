<?php
namespace keeko\test;

use keeko\core\application\AbstractApplication;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test Package
 * 
 * @license MIT
 * @author Tester
 */
class TestApplication extends AbstractApplication {

	/**
	 * @param Request $request
	 * @param string $path
	 */
	public function run(Request $request, $path) {
	}
}
