<?php

class Test_CoAuthors_Users_Screen extends CoAuthorsPlus_TestCase {

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
	 * Checks coauthors post count column is correctly added to users.php.
	 *
	 * @covers cap_filter_manage_users_columns()
	 */
	public function test_users_screen_columns() {
		global $coauthors_plus;

		$sample_columns = array(
			'beautiful_squirrel' => 'A furry tail indeed!',
			'posts' => 'An ordinary and boring column.',
			'galloping_lama' => 'Faster than the wind!'
		);

		$filtered_columns = cap_filter_manage_users_columns( $sample_columns );

		$this->assertArrayHasKey( 'coauthors_post_count', $filtered_columns );
	}

	/**
	 * Checks coauthors post count value in users.php rows is correct.
	 *
	 * @covers cap_filter_manage_users_custom_column()
	 */
	public function test_users_screen_posts_count_value() {
		global $coauthors_plus;

		$posts_count = 5;
		$test_posts = $this->factory->post->create_many( $posts_count );

		foreach( $test_posts as $post_id ) {
			$coauthors_plus->add_coauthors( $post_id, array( $this->author1->user_nicename ) );
		}

		$column_name = 'coauthors_post_count';

		$filtered_value = cap_filter_manage_users_custom_column( '', $column_name, $this->author1->ID );

		$this->assertContains( '>'.( $posts_count+1 ).'<', $filtered_value ); //author1 is added as coauthor of one post in construct
	}
}