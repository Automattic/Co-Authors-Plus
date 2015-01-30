<?php
/**
 * Test output of template tags when Co-Authors Plus is active.
 *
 * Note that these tests expect the `coauthors_auto_apply_template_tags` filter to be `true` by default due to the way
 * Co-Authors Plus and WordPress' unit tests are loaded. See bootstrap.php for the filter application. The filter is
 * selectively disabled for relevant tests.
 */
class Test_Template_Tags extends CoAuthorsPlus_TestCase {

	function setUp() {
		global $coauthors_plus;

		parent::setUp();

		$this->template_post_id = wp_insert_post( array(
			'post_author'  => $this->author1,
			'post_status'  => 'publish',
			'post_content' => rand_str(),
			'post_title'   => rand_str(),
			'post_type'    => 'post',
		) );

		// Add editor1 as a co-author of our post
		$editor1 = get_user_by( 'id', $this->editor1 );
		$coauthors_plus->add_coauthors( $this->template_post_id, array( $editor1->user_login ), true );

		// Navigate to and set up our post
		$this->go_to( get_permalink( $this->template_post_id ) );
		setup_postdata( get_post( $this->template_post_id ) );
	}

	function test_template_functions_are_filtered() {
		global $coauthors_plus_template_filters;

		// Verify that our expected filters are in place
		$this->assertEquals( 10, has_filter( 'the_author',            array( $coauthors_plus_template_filters, 'filter_the_author' ) ) );
		$this->assertEquals( 10, has_filter( 'the_author_posts_link', array( $coauthors_plus_template_filters, 'filter_the_author_posts_link' ) ) );
		$this->assertEquals( 15, has_filter( 'the_author',            array( $coauthors_plus_template_filters, 'filter_the_author_rss' ), 15 ) );
		$this->assertEquals( 10, has_action( 'rss2_item',             array( $coauthors_plus_template_filters, 'action_add_rss_guest_authors' ) ) );
	}

	function test_auto_the_author_posts_link_contains_both() {
		ob_start();
		the_author_posts_link();
		$text = ob_get_clean();

		// We don't care about specifics here, just the list of author names
		$text = wp_strip_all_tags( $text );

		$this->assertEquals( 'author1 and editor2', $text );
	}

	function test_manual_the_author_posts_link_contains_one() {
		$this->remove_auto_apply();

		ob_start();
		the_author_posts_link();
		$text = ob_get_clean();

		// We don't care about specifics here, just the list of author names
		$text = wp_strip_all_tags( $text );

		$this->assertEquals( 'author1', $text );
	}

	function test_auto_the_author_contains_both() {
		ob_start();
		the_author();
		$text = ob_get_clean();

		// We don't care about specifics here, just the list of author names
		$text = wp_strip_all_tags( $text );

		$this->assertEquals( 'author1 and editor2', $text );
	}

	function test_manual_the_author_contains_one() {
		$this->remove_auto_apply();

		ob_start();
		the_author();
		$text = ob_get_clean();

		// We don't care about specifics here, just the list of author names
		$text = wp_strip_all_tags( $text );

		$this->assertEquals( 'author1', $text );
	}

	function test_auto_the_author_rss_contains_one() {
		$this->go_to_rdf();

		ob_start();
		the_author();
		$text = ob_get_clean();

		// We don't care about specifics here, just the list of author names
		$text = wp_strip_all_tags( $text );

		// Only one author name should be shown in each author tag in feeds:
		$this->assertEquals( 'author1', $text );
	}

	function test_manual_the_author_rss_contains_one() {
		$this->remove_auto_apply();

		$this->go_to_rdf();

		ob_start();
		the_author();
		$text = ob_get_clean();

		// We don't care about specifics here, just the list of author names
		$text = wp_strip_all_tags( $text );

		// Only one author name should be shown in each author tag in feeds:
		$this->assertEquals( 'author1', $text );
	}

	function test_auto_rss2_item_contains_two() {
		$text = $this->get_feed();

		$this->assertContains( '<dc:creator><![CDATA[editor2]]></dc:creator>', $text );

	}

	function test_manual_rss2_item_contains_one() {
		$this->remove_auto_apply();

		$text = $this->get_feed();

		$this->assertFalse( strpos( $text, '<dc:creator><![CDATA[editor2]]></dc:creator>' ) );

	}

	function go_to_rdf() {
		// Navigate to and set up the RDF feed of our post
		$this->go_to( add_query_arg( 'feed', 'rdf', get_permalink( $this->template_post_id ) ) );
		setup_postdata( get_post( $this->template_post_id ) );

		$this->assertTrue( is_feed( 'rdf' ) );
	}

	function get_feed() {
		// Navigate to the site's RSS feed
		$this->go_to( get_feed_link() );

		ob_start();
		$this->do_feed();
		$text = ob_get_clean();

		$this->assertTrue( is_feed() );

		// Grab the first item in the post list
		$this->assertEquals( 1, preg_match( '|<item>(.*?)</item>|s', $text, $m ) );

		// Return the first `<item>`
		return $m[1];
	}

	function do_feed() {
		// Feed files emit a warning due to headers already being sent, so we need to silence it
		@load_template( ABSPATH . WPINC . '/feed-rss2.php', false );
	}

	function remove_auto_apply() {
		global $coauthors_plus_template_filters;

		// Disable the automatically-applied template tag filters
		remove_filter( 'the_author',            array( $coauthors_plus_template_filters, 'filter_the_author' ) );
		remove_filter( 'the_author_posts_link', array( $coauthors_plus_template_filters, 'filter_the_author_posts_link' ) );
		remove_filter( 'the_author',            array( $coauthors_plus_template_filters, 'filter_the_author_rss' ), 15 );
		remove_action( 'rss2_item',             array( $coauthors_plus_template_filters, 'action_add_rss_guest_authors' ) );
	}

}
