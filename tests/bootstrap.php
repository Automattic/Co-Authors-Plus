<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Automattic\LegacyRedirector
 */

use Yoast\WPTestUtils\WPIntegration;

require_once dirname( dirname( __FILE__ ) ) . '/vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	// Updated from default (__FILE__), since this bootstrap is an extra level down in tests/Integration/.
	require dirname( dirname( __FILE__ ) ) . '/co-authors-plus.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Make sure the Composer autoload file has been generated.
WPIntegration\check_composer_autoload_exists();

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";

/*
 * Register the custom autoloader to overload the PHPUnit MockObject classes when running on PHP 8.
 *
 * This function has to be called _last_, after the WP test bootstrap to make sure it registers
 * itself in FRONT of the Composer autoload (which also prepends itself to the autoload queue).
 */
WPIntegration\register_mockobject_autoloader();

// Add custom test case.
require __DIR__ . '/coauthorsplus-testcase.php';
