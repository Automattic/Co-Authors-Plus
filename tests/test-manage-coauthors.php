<?php

class Test_Manage_CoAuthors extends CoAuthorsPlus_TestCase {

	/**
	 * Test assigning a Co-Author to a post
	 */
	public function test_add_coauthor_to_post() {
		global $coauthors_plus;

		$coauthors = get_coauthors( $this->author1_post1 );
		$this->assertEquals( 1, count( $coauthors ) );

		// append = true, should preserve order
		$editor1 = get_user_by( 'id', $this->editor1 );
		$coauthors_plus->add_coauthors( $this->author1_post1, array( $editor1->user_login ), true );
		$coauthors = get_coauthors( $this->author1_post1 );
		$this->assertEquals( array( $this->author1, $this->editor1 ), wp_list_pluck( $coauthors, 'ID' ) );

		// append = false, overrides existing authors
		$coauthors_plus->add_coauthors( $this->author1_post1, array( $editor1->user_login ), false );
		$coauthors = get_coauthors( $this->author1_post1 );
		$this->assertEquals( array( $this->editor1 ), wp_list_pluck( $coauthors, 'ID' ) );

	}


}