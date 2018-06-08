<?php

class Test_CoAuthors_Edit_Screen extends CoAuthorsPlus_TestCase {

	public function setUp() {
		parent::setUp();

		$this->author1 = $this->factory->user->create_and_get( array( 'role' => 'author', 'user_login' => 'author1' ) );
		$this->editor1 = $this->factory->user->create_and_get( array( 'role' => 'editor', 'user_login' => 'editor1' ) );

		$this->post = $this->factory->post->create_and_get( array(
			'post_author'  => $this->author1->ID,
			'post_status'  => 'publish',
			'post_content' => rand_str(),
			'post_title'   => rand_str(),
			'post_type'    => 'post',
		) );
	}

	/**
	 * Checks coauthors column is correctly added to edit.php.
	 *
	 * @covers CoAuthors_Plus::_filter_manage_posts_columns()
	 */
	public function test_edit_screen_columns() {
		global $coauthors_plus;
		
		set_current_screen( 'edit-post' ); //need so that $coauthors_plus->is_post_type_enabled() is true

		$sample_columns = array(
			'UDP_joke' => 'But will you get it?',
			'title' => 'An ordinary and boring column.',
			'author' => 'Another ordinary and boring column, albeit this should vanish.',
			'barber_paradox' => 'Ever heard of the Barber Paradox? Ask your local Barber!'
		);

		$filtered_columns = cap_filter_manage_posts_columns( $sample_columns );

		$this->assertArrayHasKey( 'coauthors', $filtered_columns );
		$this->assertArrayNotHasKey( $coauthors_plus->coauthor_taxonomy, $filtered_columns );
	}

	/**
	 * Checks coauthor field is correct in edit.php screen.
	 *
	 * @covers cap_filter_manage_posts_custom_column()
	 */
	public function test_edit_screen_column_value() {
		global $coauthors_plus, $post;

		$post = $this->post;
		$coauthors_plus->add_coauthors( $post->ID, array( $this->author1->user_nicename ) );

		ob_start();
		cap_filter_manage_posts_custom_column( 'coauthors' );
		$filtered_value = ob_get_clean();

		$this->assertContains( 'author_name='.$this->author1->user_nicename, $filtered_value );
	}

	/**
	 * Checks edit.php filter views are correctly set up.
	 *
	 * @covers cap_filter_views()
	 */
	public function test_edit_screen_filter_views() {
		$views = array(
			'all' => 'All (9,934)',
			'mine' => 'Mine (9,924)',
			'publish' => 'Published (9,932)',
			'private' => 'Private (1)',
			'trash' => 'Trash (1)',
			'draft' => 'Draft (1)'
		);

		$filtered_views = cap_filter_views( $views );

		//'mine' already present, should not do anything
		$this->assertEquals( $views, $filtered_views );

		unset( $views['mine'] );
		$filtered_views = cap_filter_views( $views );

		$this->assertArrayHasKey( 'mine', $filtered_views );
	}
}