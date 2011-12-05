<?php
/**
 * @package Linkify_Text
 * @author Scott Reilly
 * @version 1.0
 */
/*
Plugin Name: Linkify Text
Version: 1.0
Plugin URI: http://coffee2code.com/wp-plugins/linkify-text/
Author: Scott Reilly
Author URI: http://coffee2code.com
Text Domain: linkify-text
Domain Path: /lang/
Description: Automatically hyperlink words or phrases in your posts.

Compatible with WordPress 3.0+, 3.1+, 3.2+, 3.3+.

=>> Read the accompanying readme.txt file for instructions and documentation.
=>> Also, visit the plugin's homepage for additional information and updates.
=>> Or visit: http://wordpress.org/extend/plugins/linkify-text/

TODO
	* Allow links to point to other text entries so a link can be defined once:
	    WP => http://wordpress.org
	    WordPress => WP
	    start blogging => WP
*/

/*
Copyright (c) 2011 by Scott Reilly (aka coffee2code)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy,
modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/


if ( ! class_exists( 'c2c_LinkifyText' ) ) :

require_once( 'c2c-plugin.php' );

class c2c_LinkifyText extends C2C_Plugin_029 {

	public static $instance;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->c2c_LinkifyText();
	}

	public function c2c_LinkifyText() {
		// Be a singleton
		if ( ! is_null( self::$instance ) )
			return;

		parent::__construct( '1.0', 'linkify-text', 'c2c', __FILE__, array() );
		register_activation_hook( __FILE__, array( __CLASS__, 'activation' ) );
		self::$instance = $this;
	}

	/**
	 * Handles activation tasks, such as registering the uninstall hook.
	 *
	 * @return void
	 */
	public function activation() {
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Handles uninstallation tasks, such as deleting plugin options.
	 *
	 * This can be overridden.
	 *
	 * @return void
	 */
	public static function uninstall() {
		delete_option( 'c2c_linkify_text' );
	}

	/**
	 * Override the plugin framework's register_filters() to actually hook actions and filters.
	 *
	 * @return void
	 */
	public function register_filters() {
		$filters = apply_filters( 'c2c_linkify_text_filters', array( 'the_content', 'the_excerpt', 'widget_text' ) );
		foreach ( (array) $filters as $filter )
			add_filter( $filter, array( &$this, 'linkify_text' ), 2 );

		// Note that the priority must be set high enough to avoid links inserted by the plugin from
		// getting omitted as a result of any link stripping that may be performed.
		$options = $this->get_options();
		if ( apply_filters( 'c2c_linkify_text_comments', $options['linkify_text_comments'] ) ) {
			add_filter( 'get_comment_text',    array( &$this, 'linkify_text' ), 11 );
			add_filter( 'get_comment_excerpt', array( &$this, 'linkify_text' ), 11 );
		}
	}

	/**
	 * Initializes the plugin's configuration and localizable text variables.
	 *
	 * @return void
	 */
	public function load_config() {
		$this->name      = __( 'Linkify Text', $this->textdomain );
		$this->menu_name = __( 'Linkify Text', $this->textdomain );

		$this->config = array(
			'text_to_link' => array( 'input' => 'inline_textarea', 'datatype' => 'hash', 'default' => array(
					"WordPress"   => "http://wordpress.org",
					"coffee2code" => "http://coffee2code.com"
				),
				'allow_html' => true, 'no_wrap' => true, 'input_attributes' => 'rows="15" cols="40"',
				'label' => __( 'Text and Links', $this->textdomain ),
				'help' => __( 'Define only one text and associated link per line, and don\'t span lines.', $this->textdomain )
			),
			'linkify_text_comments' => array( 'input' => 'checkbox', 'default' => false,
					'label' => __( 'Enable text linkification in comments?', $this->textdomain ),
					'help' => ''
			),
			'case_sensitive' => array( 'input' => 'checkbox', 'default' => false,
					'label' => __( 'Case sensitive text matching?', $this->textdomain ),
					'help' => __( 'If checked, then linkification of WordPress would also affect wordpress.', $this->textdomain )
			)
		);
	}

	/**
	 * Outputs the text above the setting form
	 *
	 * @return void (Text will be echoed.)
	 */
	public function options_page_description() {
		parent::options_page_description( __( 'Linkify Text Settings', $this->textdomain ) );

		echo '<p>' . __( 'Description: Automatically hyperlink words or phrases in your posts.', $this->textdomain ) . '</p>';
		echo '<p>' . __( 'Define text and the URL they should be linked to in the field below. The format should be like this:', $this->textdomain ) . '</p>';
		echo "<blockquote><code>WordPress => http://wordpress.org</code></blockquote>";
		echo '<p>' . __( 'Where <code>WordPress</code> is the text you want to get linked and <code>http://wordpress.org</code> would be what the target for that link.', $this->textdomain ) . '</p>';
		echo '<p>' . __( 'Other considerations:', $this->textdomain ) . '</p>';
		echo '<ul class="c2c-plugin-list"><li>';
		echo __( 'List the more specific matches early, to avoid stomping on another of your links.  For example, if you have both <code>WordPress</code> and <code>WordPress Support Forums</code> as text to be linked, put <code>WordPress Support Forums</code> first; otherwise, the <code>WordPress</code> entry will match first, preventing the phrase <code>WordPress Support Forums</code> from ever being found.', $this->textdomain );
		echo '</li><li>';
		echo __( 'Text must be represent word or phrase, not partial string.', $this->textdomain );
		echo '</li><li>';
		echo __( 'If the protocol is not specified, then \'http://\' is assumed.', $this->textdomain );
		echo '</li></ul>';
	}

	/**
	 * Perform text linkification.
	 *
	 * @param string $text Text to be processed for text linkification
	 * @return string Text with replacements already processed
	 */
	public function linkify_text( $text ) {
		$options         = $this->get_options();
		$text_to_link    = apply_filters( 'c2c_linkify_text',                $options['text_to_link'] );
		$case_sensitive  = apply_filters( 'c2c_linkify_text_case_sensitive', $options['case_sensitive'] );
		$preg_flags      = $case_sensitive ? 's' : 'si';

		$text = ' ' . $text . ' ';
		if ( ! empty( $text_to_link ) ) {
			foreach ( $text_to_link as $old_text => $link ) {
				// If the link does not contain a protocol and isn't absolute, prepend 'http://'
				// Sorry, not supporting non-root relative paths.
				if ( strpos( $link, '://' ) === false && ! path_is_absolute( $link ) )
					$link = 'http://' . $link;
				$new_text = '<a href="' . esc_url( $link ) . '" title="">' . $old_text . '</a>';
				$new_text = apply_filters( 'c2c_linkify_text_linked_text', $new_text, $old_text, $link );
				$text = preg_replace( "|(?!<.*?)\b$old_text\b(?![^<>]*?>)|$preg_flags", $new_text, $text );
			}
			// Remove links within links
			$text = preg_replace( "#(<a [^>]+>)(.*)<a [^>]+>([^<]*)</a>([^>]*)</a>#iU", "$1$2$3$4</a>" , $text );
		}
		return trim( $text );
	} //end linkify_text()

} // end c2c_LinkifyText

// To access the instance of the created object, use c2c_TextReplace::$instance
new c2c_LinkifyText();

endif; // end if !class_exists()

?>