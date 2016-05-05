<?php

/**
 * Provides usage for this plugin to be used with WP REST
 */
class CoAuthors_API {

	/**
	 * @var CoAuthors_API_Controller
	 */
	private $controllers;

	/**
	 * Adds a controller so that it gets included in the boot process.
	 *
	 * @param CoAuthors_API_Controller $controller
	 */
	public function register_controller( CoAuthors_API_Controller $controller ) {
		$this->controllers[] = $controller;
	}

	/**
	 * Loads all routes from any controller inside the $controllers array.
	 */
	public function boot() {
		foreach ( $this->controllers as $controller ) {
			$controller->create_routes();
		}
	}

}