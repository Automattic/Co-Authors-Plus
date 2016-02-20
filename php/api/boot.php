<?php

/**
 * CoAuthors Plus REST API Boot file.
 * It loads all the required classes need by it.
 */
global $wp_version;

if ( version_compare( $wp_version, '4.4', '<' ) ) {
    return;
}

require_once( dirname( __FILE__ ) . '/class-coauthors-api.php' );
require_once( dirname( __FILE__ ) . '/class-coauthors-api-controller.php' );
require_once( dirname( __FILE__ ) . '/class-coauthors-api-search.php' );
require_once( dirname( __FILE__ ) . '/class-coauthors-api-post.php' );

define('COAUTHORS_PLUS_API_NAMESPACE', 'coauthors/');
define('COAUTHORS_PLUS_API_VERSION', 'v1');

$coauthors_api = new CoAuthors_API();
$coauthors_api->register_controller( new CoAuthors_API_Search() );
$coauthors_api->register_controller( new CoAuthors_API_Post() );

add_action( 'rest_api_init', array( $coauthors_api, 'boot' ) );