<?php

class Test_CoAuthors_Integration_Edit_Flow extends CoAuthorsPlus_TestCase {

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
	 * Checks Edit Flow calendar item information fields are correct when more than one coauthor is present.
	 *
	 * @covers cap_filter_ef_calendar_item_information_fields()
	 */
	public function test_ef_calendar_item_information_fields() {
		global $coauthors_plus;

		$coauthors_plus->add_coauthors( $this->post->ID, array( $this->author1->user_nicename, $this->editor1->user_nicename ) );

		$EF_information_fields = array(
			'author' => array(
				'label' => 'Author',
				'value' => $this->author1->display_name,
				'type' => 'author'
			)
		);

		$filtered_fields = cap_filter_ef_calendar_item_information_fields( $EF_information_fields, $this->post->ID );

		$expected_fields = $EF_information_fields;
		$expected_fields['author']['value'] = $this->author1->display_name.', '.$this->editor1->display_name;

		$this->assertEquals( $expected_fields['author']['value'], $filtered_fields['author']['value'] );
	}

	/**
	 * Checks Edit Flow calendar item information fields are correct when more than one coauthor is present.
	 *
	 * @covers cap_filter_ef_calendar_item_information_fields()
	 */
	public function test_ef_calendar_item_information_fields_when_no_author_field() {
		global $coauthors_plus;

		$coauthors_plus->add_coauthors( $this->post->ID, array( $this->author1->user_nicename, $this->editor1->user_nicename ) );

		$EF_information_fields = array();

		$filtered_fields = cap_filter_ef_calendar_item_information_fields( $EF_information_fields, $this->post->ID );

		$expected_fields = $EF_information_fields;

		$this->assertEquals( $expected_fields, $filtered_fields);
	}

	/**
	 * Checks Edit Flow story budget column value is correct when more than one coauthor is present.
	 *
	 * @covers cap_filter_ef_story_budget_term_column_value()
	 */ 
	public function test_ef_story_budget_term_column_value() {
		global $coauthors_plus;

		$coauthors_plus->add_coauthors( $this->post->ID, array( $this->author1->user_nicename, $this->editor1->user_nicename ) );

		$column_name = $coauthors_plus->coauthor_taxonomy;
		$parent_term = $this->factory->term->create_and_get();
		
		$filtered_value = cap_filter_ef_story_budget_term_column_value( $column_name, $this->post, $parent_term );
		
		$expected_value = $this->author1->user_nicename.', '.$this->editor1->user_nicename;
		
		$this->assertEquals( $expected_value, $filtered_value );
	}
}