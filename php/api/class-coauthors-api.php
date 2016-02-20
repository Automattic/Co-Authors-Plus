<?php

/**
 * Provides usage for this plugin to be used with WP REST
 */
class CoAuthors_API {

    /**
     * @var CoAuthorsApiRestBase
     */
    private $controllers;

    public function register_controller( CoAuthors_API_Controller $controller ) {
        $this->controllers[] = $controller;
    }

    public function boot() {
        foreach( $this->controllers as $controller ) {
            $controller->create_routes();
        }
    }

}