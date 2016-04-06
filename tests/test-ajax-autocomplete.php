<?php

/**
 * Test AJAX autocomplete features
 *
 * @group ajax
 */
class Test_Ajax_Autocomplete extends CoAuthorsPlus_Ajax_TestCase {

	/** 
	 * Test that AJAX should return status:false when no query is provided
	 */
	public function test_empty_query() {
		$_POST['nonce'] = wp_create_nonce( 'coauthors' );
		$_POST['q'] = '';
		$_POST['exclude'] = array();

		try {
			$this->_handleAjax( 'coauthors_ajax_suggest' );
		} 
		catch ( WPAjaxDieContinueException $e ) {} 
		catch ( WPAjaxDieStopExecution $e ) {}

		$response = json_decode( $this->_last_response );

		// Test that we have a success=false response
		$this->assertInternalType( 'object', $response );
		$this->assertObjectHasAttribute( 'success', $response );
		$this->assertFalse( $response->success );
	}

	/**
	 * Test that AJAX should return a valid author object when a valid query is performed
	 */
	public function test_valid_query() {
		$_POST['nonce'] = wp_create_nonce( 'coauthors' );
		$_POST['q'] = 'author1';
		$_POST['exclude'] = array();

		try {
			$this->_handleAjax( 'coauthors_ajax_suggest' );
		} 
		catch ( WPAjaxDieContinueException $e ) {} 
		catch ( WPAjaxDieStopExecution $e ) {}

		$response = json_decode( $this->_last_response );

		// Test that we have a successful response
		$this->assertInternalType( 'object', $response );
		$this->assertObjectHasAttribute( 'success', $response );
		$this->assertTrue( $response->success );

		// Test that we have data in our response
		$this->assertObjectHasAttribute( 'data', $response );
		$this->assertEquals( 1, count( $response->data ) );
		$this->assertEquals( 'author1', $response->data[0]->login );
	}

	/**
	 * Test that we get zero results for a query that doesn't exist
	 */
	public function test_nonexistent_query() {
		$_POST['nonce'] = wp_create_nonce( 'coauthors' );
		$_POST['q'] = 'some_nonexistent_query';
		$_POST['exclude'] = array();

		try {
			$this->_handleAjax( 'coauthors_ajax_suggest' );
		} 
		catch ( WPAjaxDieContinueException $e ) {} 
		catch ( WPAjaxDieStopExecution $e ) {}

		$response = json_decode( $this->_last_response );

		// Test that we have a successful response
		$this->assertInternalType( 'object', $response );
		$this->assertObjectHasAttribute( 'success', $response );
		$this->assertTrue( $response->success );

		// Test that we have no data in response
		$this->assertObjectHasAttribute( 'data', $response );
		$this->assertEquals( 0, count( $response->data ) );
	}

}