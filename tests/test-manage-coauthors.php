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

	/**
	 * When a co-author is assigned to a post, the post author value
	 * should be set appropriately
	 * 
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/140
	 */
	public function test_add_coauthor_updates_post_author() {
		global $coauthors_plus;

		// append = true, preserves existing post_author
		$editor1 = get_user_by( 'id', $this->editor1 );
		$coauthors_plus->add_coauthors( $this->author1_post1, array( $editor1->user_login ), true );
		$this->assertEquals( $this->author1, get_post( $this->author1_post1 )->post_author );

		// append = false, overrides existing post_author
		$coauthors_plus->add_coauthors( $this->author1_post1, array( $editor1->user_login ), false );
		$this->assertEquals( $this->editor1, get_post( $this->author1_post1 )->post_author );

	}


}