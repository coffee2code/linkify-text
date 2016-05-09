<?php

class Linkify_Text_Test extends WP_UnitTestCase {

	protected static $text_to_link = array(
		'coffee2code'    => 'http://coffee2code.com',
		'Matt Mullenweg' => 'http://ma.tt',
		'BuddyPress'     => 'buddypress.org',
		'my posts'       => '/authors/scott',
		'my readme'      => 'readme.html',
		'Scott Reilly'   => ':coffee2code',
		'c2c'            => ':coffee2code',
		'me'             => ':c2c',
		'ref dne'        => ':nonexistent',
		'not a link'     => 'some text',
		'blank'          => '',
		'Cocktail glacé' => 'http://www.domain.com/cocktail-glace.html',
		'AT&T'           => 'http://att.com',
		'漢字はユニコード'  => 'http://php.net/manual/en/ref.mbstring.php',
	);

	function setUp() {
		parent::setUp();
		$this->set_option();
	}

	function tearDown() {
		parent::tearDown();

		// Reset options
		$this->set_option();

		remove_filter( 'c2c_linkify_text',                array( $this, 'add_text_to_linkify' ) );
		remove_filter( 'c2c_linkify_text_replace_once',   '__return_true' );
		remove_filter( 'c2c_linkify_text_case_sensitive', '__return_true' );
		remove_filter( 'c2c_linkify_text_comments',       '__return_true' );
		remove_filter( 'c2c_linkify_text_filters',        array( $this, 'add_custom_filter' ) );
		remove_filter( 'c2c_linkify_text_linked_text',    array( $this, 'add_title_attribute_to_linkified_text' ), 10, 3 );
	}



	/*
	 *
	 * DATA PROVIDERS
	 *
	 */



	public static function get_default_filters() {
		return array(
			array( 'the_content' ),
			array( 'the_excerpt' ),
			array( 'widget_text' ),
		);
	}

	public static function get_comment_filters() {
		return array(
			array( 'get_comment_text' ),
			array( 'get_comment_excerpt' ),
		);
	}

	public static function get_text_to_link() {
		return array_map( function($v) { return array( $v ); }, array_keys( self::$text_to_link ) );
	}



	/*
	 *
	 * HELPER FUNCTIONS
	 *
	 */



	function set_option( $settings = array() ) {
		$defaults = array(
			'text_to_link' => self::$text_to_link,
		);
		$settings = wp_parse_args( $settings, $defaults );
		c2c_LinkifyText::get_instance()->update_option( $settings, true );
	}

	function linkify_text( $text ) {
		return c2c_LinkifyText::get_instance()->linkify_text( $text );
	}

	function expected_link( $text, $link ) {
		return '<a href="' . $link . '">' . $text . '</a>';
	}

	function add_text_to_linkify( $text_to_link ) {
		$text_to_link = (array) $text_to_link;
		$text_to_link['bbPress'] = 'https://bbpress.org';
		return $text_to_link;
	}

	function add_custom_filter( $filters ) {
		$filters[] = 'custom_filter';
		return $filters;
	}

	// Taken from example in readme.txt.
	function add_title_attribute_to_linkified_text( $display_link, $text_to_link, $link_for_text  ) {
		// The string that you chose to separate the link URL and the title attribute text.
		$separator = ' || ';

		// Only change the linked text if a title has been defined
		if ( false !== strpos( $link_for_text, $separator ) ) {
			// Get the link and title that was defined for the text to be linked.
			list( $link, $title ) = explode( $separator, $link_for_text );

			// Make the link the way you want.
			$display_link = '<a href="' . esc_url( $link ) . '" title="' . $title . '">' . $text_to_link . '</a>';
		}

		return $display_link;
	}



	/*
	 *
	 * TESTS
	 *
	 */



	function test_class_exists() {
		$this->assertTrue( class_exists( 'c2c_LinkifyText' ) );
	}

	function test_plugin_framework_class_name() {
		$this->assertTrue( class_exists( 'c2c_LinkifyText_Plugin_042' ) );
	}

	function test_plugin_framework_version() {
		$this->assertEquals( '042', c2c_LinkifyText::get_instance()->c2c_plugin_version() );
	}

	function test_get_version() {
		$this->assertEquals( '1.7', c2c_LinkifyText::get_instance()->version() );
	}

	function test_instance_object_is_returned() {
		$this->assertTrue( is_a( c2c_LinkifyText::get_instance(), 'c2c_LinkifyText' ) );
	}

	function test_linkifies_text() {
		$expected = $this->expected_link( 'coffee2code', 'http://coffee2code.com' );

		$this->assertEquals( $expected, $this->linkify_text( 'coffee2code' ) );
		$this->assertEquals( "ends with $expected", $this->linkify_text( 'ends with coffee2code' ) );
		$this->assertEquals( "ends with period $expected.", $this->linkify_text( 'ends with period coffee2code.' ) );
		$this->assertEquals( "$expected starts", $this->linkify_text( 'coffee2code starts' ) );

		$this->assertEquals( $this->expected_link( 'Matt Mullenweg', 'http://ma.tt' ), $this->linkify_text( 'Matt Mullenweg' ) );
	}

	function test_linkifies_text_with_ampersand() {
		$this->assertEquals( $this->expected_link( 'AT&T', self::$text_to_link[ 'AT&T' ] ), $this->linkify_text( 'AT&T' ) );
	}

	function test_linkifies_text_with_html_encoded_amp_ampersand() {
		$this->assertEquals( $this->expected_link( 'AT&T', self::$text_to_link[ 'AT&T' ] ), $this->linkify_text( 'AT&amp;T' ) );
	}

	function test_linkifies_text_with_html_encoded_038_ampersand() {
		$this->assertEquals( $this->expected_link( 'AT&T', self::$text_to_link[ 'AT&T' ] ), $this->linkify_text( 'AT&#038;T' ) );
	}

	function test_linkifies_multibyte_text() {
		$mb_string = '漢字はユニコード';

		$this->assertEquals( $this->expected_link( $mb_string, self::$text_to_link[ $mb_string ] ), $this->linkify_text( $mb_string ) );
	}

	function test_linkifies_single_term_multiple_times() {
		$expected = $this->expected_link( 'coffee2code', 'http://coffee2code.com' );

		$this->assertEquals( "$expected $expected $expected", $this->linkify_text( 'coffee2code coffee2code coffee2code' ) );
	}

	function test_does_not_linkify_substrings() {
		$this->assertEquals( 'xcoffee2code',  $this->linkify_text( 'xcoffee2code' ) );
		$this->assertEquals( 'ycoffee2codey', $this->linkify_text( 'ycoffee2codey' ) );
		$this->assertEquals( 'coffee2codez',  $this->linkify_text( 'coffee2codez' ) );
	}

	function test_does_not_link_within_links() {
		$expected = '<a href="http://coffee2code.net">coffee2code</a>';

		$this->assertEquals( $expected, $this->linkify_text( $expected ) );
	}

	function test_does_not_link_within_shortcodes() {
		$expected = '[code user="coffee2code"] coffee2code [/code]';

		$this->assertEquals( $expected, $this->linkify_text( $expected ) );
	}

	function test_empty_link_does_not_linkify_text() {
		$this->assertEquals( 'blank', $this->linkify_text( 'blank' ) );
	}

	function test_linkifies_with_case_insensitivity() {
		$expected = $this->expected_link( 'coffee2code', 'http://coffee2code.com' );

		$this->assertEquals( $expected, $this->linkify_text( 'coffee2code' ) );
		$this->assertEquals( $expected, $this->linkify_text( 'Coffee2code' ) );
		$this->assertEquals( $expected, $this->linkify_text( 'COFFEE2CODE' ) );
	}

	function test_linkifies_text_with_special_character() {
		$expected = $this->expected_link( 'Cocktail glacé', 'http://www.domain.com/cocktail-glace.html' );

		$this->assertEquals( $expected, $this->linkify_text( 'Cocktail glacé' ) );
	}

	// This affirms a limitation: mb_ereg_replace() (as used for search strings
	// with multibyte characters) does't support limiting number of replacements.
	//
	// Ideally this test should fail with only one replacement performed at which
	// time this test can be changed.
	function test_linkifies_text_with_special_character_multiple_times_despite_a_limit() {
		$expected = $this->expected_link( 'Cocktail glacé', 'http://www.domain.com/cocktail-glace.html' );

		$this->set_option( array( 'replace_once' => true ) );

		$this->assertEquals( "$expected $expected $expected", $this->linkify_text( 'Cocktail glacé Cocktail glacé Cocktail glacé' ) );
	}

	function test_linkifies_text_via_term_referencing() {
		$this->assertEquals( $this->expected_link( 'Scott Reilly', 'http://coffee2code.com' ), $this->linkify_text( 'Scott Reilly' ) );
		$this->assertEquals( $this->expected_link( 'c2c', 'http://coffee2code.com' ), $this->linkify_text( 'c2c' ) );
	}

	function test_does_not_linkify_text_via_referencing_nonexistent_term() {
		$this->assertEquals( 'ref dne', $this->linkify_text( 'ref dne' ) );
	}

	// NOTE: This could eventually be supported, though circular referencing should be accounted for.
	function test_does_not_linkify_text_via_referencing_another_term() {
		$this->assertEquals( 'me', $this->linkify_text( 'me' ) );
	}

	// NOTE: Not trying to be too sophisticted here.
	function test_does_not_linkify_text_if_link_does_not_look_like_link() {
		$this->assertEquals( 'not a link', $this->linkify_text( 'not a link' ) );
	}

	function test_linkifies_once_via_setting() {
		$expected = $this->expected_link( 'coffee2code', 'http://coffee2code.com' );
		$this->test_linkifies_single_term_multiple_times();
		$this->set_option( array( 'replace_once' => true ) );

		$this->assertEquals( "$expected coffee2code coffee2code", $this->linkify_text( 'coffee2code coffee2code coffee2code' ) );
	}

	function test_linkifies_once_via_trueish_setting_value() {
		$expected = $this->expected_link( 'coffee2code', 'http://coffee2code.com' );
		$this->test_linkifies_single_term_multiple_times();
		$this->set_option( array( 'replace_once' => '1' ) );

		$this->assertEquals( "$expected coffee2code coffee2code", $this->linkify_text( 'coffee2code coffee2code coffee2code' ) );
	}

	function test_linkifies_once_via_filter() {
		$expected = $this->expected_link( 'coffee2code', 'http://coffee2code.com' );
		$this->test_linkifies_single_term_multiple_times();
		add_filter( 'c2c_linkify_text_replace_once', '__return_true' );

		$this->assertEquals( "$expected coffee2code coffee2code", $this->linkify_text( 'coffee2code coffee2code coffee2code' ) );
	}

	function test_linkifies_with_case_sensitivity_via_setting() {
		$expected = $this->expected_link( 'coffee2code', 'http://coffee2code.com' );
		$this->test_linkifies_with_case_insensitivity();
		$this->set_option( array( 'case_sensitive' => true ) );

		$this->assertEquals( $expected, $this->linkify_text( 'coffee2code' ) );
		$this->assertEquals( 'Coffee2code', $this->linkify_text( 'Coffee2code' ) );
		$this->assertEquals( 'COFFEE2CODE', $this->linkify_text( 'COFFEE2CODE' ) );
	}

	function test_linkifies_with_case_sensitivity_via_filter() {
		$expected = $this->expected_link( 'coffee2code', 'http://coffee2code.com' );
		$this->test_linkifies_with_case_insensitivity();
		add_filter( 'c2c_linkify_text_case_sensitive', '__return_true' );

		$this->assertEquals( $expected, $this->linkify_text( 'coffee2code' ) );
		$this->assertEquals( 'Coffee2code', $this->linkify_text( 'Coffee2code' ) );
		$this->assertEquals( 'COFFEE2CODE', $this->linkify_text( 'COFFEE2CODE' ) );
	}

	function test_linkifies_term_added_via_filter() {
		$this->assertEquals( 'bbPress', $this->linkify_text( 'bbPress' ) );
		$expected = $this->expected_link( 'bbPress', 'https://bbpress.org' );
		add_filter( 'c2c_linkify_text', array( $this, 'add_text_to_linkify' ) );

		$this->assertEquals( $expected, $this->linkify_text( 'bbPress' ) );
	}

	function test_linkification_prepends_protocol_if_missing_and_not_root_relative() {
		$expected = $this->expected_link( 'BuddyPress', 'http://buddypress.org' );

		$this->assertEquals( $expected, $this->linkify_text( 'BuddyPress' ) );
	}

	function test_linkification_accepts_root_relative_link() {
		$expected = $this->expected_link( 'my posts', '/authors/scott' );

		$this->assertEquals( $expected, $this->linkify_text( 'my posts' ) );
	}

	function test_linkification_accepts_root_relative_file() {
		$text = 'my readme';
		$expected = $this->expected_link( $text, 'http://' . self::$text_to_link[ $text ] );

		$this->assertEquals( $expected, $this->linkify_text( $text ) );
	}

	/**
	 * @dataProvider get_default_filters
	 */
	function test_linkification_applies_to_default_filters( $filter ) {
		$expected = $this->expected_link( 'coffee2code', 'http://coffee2code.com' );

		$this->assertNotFalse( has_filter( $filter, array( c2c_LinkifyText::get_instance(), 'linkify_text' ), 2 ) );
		$this->assertGreaterThan( 0, strpos( apply_filters( $filter, 'a coffee2code' ), $expected ) );
	}

	/**
	 * @dataProvider get_comment_filters
	 */
	function test_linkification_does_not_apply_to_comments_by_default( $filter ) {
		$this->assertEquals( 'coffee2code', apply_filters( $filter, 'coffee2code' ) );
	}

	/**
	 * @dataProvider get_comment_filters
	 */
	function test_linkification_applies_to_comment_filters_when_enabled( $filter ) {
		$expected = $this->expected_link( 'coffee2code', 'http://coffee2code.com' );

		add_filter( 'c2c_linkify_text_comments', '__return_true' );

		$this->assertNotFalse( has_filter( $filter, array( c2c_LinkifyText::get_instance(), 'linkify_comment_text' ), 11 ) );
		$this->assertGreaterThan( 0, strpos( apply_filters( $filter, 'a coffee2code' ), $expected ) );
	}

	/**
	 * @dataProvider get_comment_filters
	 */
	function test_linkification_applies_to_comments_via_setting( $filter ) {
		$expected = $this->expected_link( 'coffee2code', 'http://coffee2code.com' );
		$this->test_linkification_does_not_apply_to_comments_by_default( $filter );
		$this->set_option( array( 'linkify_text_comments' => true ) );

		$this->assertEquals( $expected, apply_filters( $filter, 'coffee2code' ) );
	}

	/**
	 * @dataProvider get_comment_filters
	 */
	function test_linkification_applies_to_comments_via_filter( $filter ) {
		$expected = $this->expected_link( 'coffee2code', 'http://coffee2code.com' );
		$this->test_linkification_does_not_apply_to_comments_by_default( $filter );
		add_filter( 'c2c_linkify_text_comments', '__return_true' );

		$this->assertEquals( $expected, apply_filters( $filter, 'coffee2code' ) );
	}

	function test_linkification_applies_to_custom_filter_via_filter() {
		$this->assertEquals( 'coffee2code', apply_filters( 'custom_filter', 'coffee2code' ) );

		add_filter( 'c2c_linkify_text_filters', array( $this, 'add_custom_filter' ) );

		c2c_LinkifyText::get_instance()->register_filters(); // Plugins would typically register their filter before this originally fires

		$this->assertEquals( $this->expected_link( 'coffee2code', 'http://coffee2code.com' ), apply_filters( 'custom_filter', 'coffee2code' ) );
	}

	// NOTE: This is a test of an example given in the readme.
	function test_defining_custom_link_markup_via_filter() {
		// Redine text_to_link to add title attribute text after the link in the link text.
		$this->set_option( array( 'text_to_link' => array( 'coffee2code' => 'http://coffee2code.com || Scott Reilly' ) ) );
		// Add custom handler via filter.
		add_filter( 'c2c_linkify_text_linked_text', array( $this, 'add_title_attribute_to_linkified_text' ), 10, 3 );

		$this->assertEquals(
			'<a href="http://coffee2code.com" title="Scott Reilly">coffee2code</a>',
			$this->linkify_text( 'coffee2code' )
		);
	}

}
