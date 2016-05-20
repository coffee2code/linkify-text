<?php
/**
 * Plugin Name: Linkify Text
 * Version:     1.8
 * Plugin URI:  http://coffee2code.com/wp-plugins/linkify-text/
 * Author:      Scott Reilly
 * Author URI:  http://coffee2code.com/
 * Text Domain: linkify-text
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Description: Automatically hyperlink words or phrases in your posts.
 *
 * Compatible with WordPress 4.1 through 4.5+.
 *
 * =>> Read the accompanying readme.txt file for instructions and documentation.
 * =>> Also, visit the plugin's homepage for additional information and updates.
 * =>> Or visit: https://wordpress.org/plugins/linkify-text/
 *
 * TODO
 * - Add class to link. Maybe add filter as well. (Q was asked about opening link in new window)
 * - Setting to open links in new window? (Forum request)
 * - Setting to prevent linkification if link points to current page? (Forum request)
 * - For multibyte strings to be linkified, honor the replace_once setting.
 * - Consider adding more options: specific number of replacements, open links in new tab, other
 *   common site places to filter
 * - Handle HTML special characters that Visual editor converts (like how '&' becomes '&amp;',
 *   which is explicitly handled). Are there others that should be handled?
 * - Inline documentation for hooks.
 *
 * @package Linkify_Text
 * @author  Scott Reilly
 * @version 1.8
 */

/*
	Copyright (c) 2011-2016 by Scott Reilly (aka coffee2code)

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

defined( 'ABSPATH' ) or die();

if ( ! class_exists( 'c2c_LinkifyText' ) ) :

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'c2c-plugin.php' );

final class c2c_LinkifyText extends c2c_LinkifyText_Plugin_043 {

	/**
	 * The one true instance.
	 *
	 * @var c2c_LinkifyText
	 */
	private static $instance;

	/**
	 * Get singleton instance.
	 *
	 * @since 1.5
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		parent::__construct( '1.8', 'linkify-text', 'c2c', __FILE__, array() );
		register_activation_hook( __FILE__, array( __CLASS__, 'activation' ) );

		return self::$instance = $this;
	}

	/**
	 * Handles activation tasks, such as registering the uninstall hook.
	 */
	public static function activation() {
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Handles uninstallation tasks, such as deleting plugin options.
	 */
	public static function uninstall() {
		delete_option( 'c2c_linkify_text' );
	}

	/**
	 * Initializes the plugin's configuration and localizable text variables.
	 */
	protected function load_config() {
		$this->name      = __( 'Linkify Text', 'linkify-text' );
		$this->menu_name = __( 'Linkify Text', 'linkify-text' );

		$this->config = array(
			'text_to_link' => array(
				'input'            => 'inline_textarea',
				'datatype'         => 'hash',
				'default'          => array(
					"WordPress"    => "https://wordpress.org",
					"coffee2code"  => "http://coffee2code.com"
				),
				'allow_html'       => true,
				'no_wrap'          => true,
				'input_attributes' => 'rows="15" cols="40"',
				'label'            => __( 'Text and Links', 'linkify-text' ),
				'help'             => __( 'Define only one text and associated link per line, and don\'t span lines.', 'linkify-text' )
					. '<br />'
					. __( 'Use a colon-prefixed term instead of a link to point to that term\'s link, e.g. <code>WP => :WordPress</code> will use the same link defined for WordPress', 'linkify-text' ),
			),
			'linkify_text_comments' => array(
				'input'            => 'checkbox',
				'default'          => false,
				'label'            => __( 'Enable text linkification in comments?', 'linkify-text' ),
			),
			'replace_once' => array(
				'input'            => 'checkbox',
				'default'          => false,
				'label'            => __( 'Limit linkifications to once per term per post?', 'linkify-text' ),
				'help'             => __( 'If checked, then each term will only be linkified the first time it appears in a post.', 'linkify-text' ),
			),
			'case_sensitive' => array(
				'input'            => 'checkbox',
				'default'          => false,
				'label'            => __( 'Case sensitive text matching?', 'linkify-text' ),
				'help'             => __( 'If checked, then linkification of WordPress would also affect wordpress.', 'linkify-text' )
					. ' '
					. __( 'NOTE: If the text to be linked contains multibyte characters, this setting is not honored.', 'linkify-text' ),
			),
		);
	}

	/**
	 * Override the plugin framework's register_filters() to actually hook actions and filters.
	 */
	public function register_filters() {
		$filters = apply_filters( 'c2c_linkify_text_filters', array( 'the_content', 'the_excerpt', 'widget_text' ) );
		foreach ( (array) $filters as $filter ) {
			add_filter( $filter, array( $this, 'linkify_text' ), 2 );
		}

		add_filter( 'get_comment_text',    array( $this, 'linkify_comment_text' ), 11 );
		add_filter( 'get_comment_excerpt', array( $this, 'linkify_comment_text' ), 11 );
	}

	/**
	 * Outputs the text above the setting form.
	 *
	 * @param string $localized_heading_text Optional. Localized page heading text.
	 */
	public function options_page_description( $localized_heading_text = '' ) {
		parent::options_page_description( __( 'Linkify Text Settings', 'linkify-text' ) );

		echo '<p>' . __( 'Description: Automatically hyperlink words or phrases in your posts.', 'linkify-text' ) . '</p>';
		echo '<p>' . __( 'Define text and the URL they should be linked to in the field below. The format should be like this:', 'linkify-text' ) . '</p>';
		echo "<blockquote><code>WordPress => https://wordpress.org</code></blockquote>";
		echo '<p>' . __( 'Where <code>WordPress</code> is the text you want to get linked and <code>https://wordpress.org</code> would be what the target for that link.', 'linkify-text' ) . '</p>';
		echo '<p>' . __( 'You can link multiple terms to the same link and only have to define the link once. Simply provide the link for a given term, then for subsequent terms sharing the same link, use the original term prepended with a colon as the link, e.g.', 'linkify-text' ) . '</p>';
		echo '<blockquote><pre><code>WP => https://wordpress.org
WordPress => :WP
dotorg => :WP
</code></pre></blockquote>';
		echo '<p>' . sprintf( __( 'All of the above terms would link to %s.', 'linkify-text' ), 'https://wordpress.org' ) . '</p>';
		echo '<p>' . __( 'NOTE: A referenced term must have a link; it cannot be a reference to another term.', 'linkify-text' ) . '</p>';
		echo '<p>' . __( 'Other considerations:', 'linkify-text' ) . '</p>';
		echo '<ul class="c2c-plugin-list"><li>';
		echo __( 'Text must represent a whole word or phrase, not a partial string.', 'linkify-text' );
		echo '</li><li>';
		echo __( 'If the protocol is not specified, then \'http://\' is assumed.', 'linkify-text' );
		echo '</li></ul>';
	}

	/**
	 * Linkifies comment text if enabled.
	 *
	 * Note that the priority when hooking this function as a callback on a filter
	 * must be set high enough to avoid links inserted by the plugin from getting
	 * omitted as a result of any link stripping that may be performed.
	 *
	 * @since 1.5
	 *
	 * @param  string $text The comment text.
	 * @return string
	 */
	public function linkify_comment_text( $text ) {
		$options = $this->get_options();
		if ( apply_filters( 'c2c_linkify_text_comments', (bool) $options['linkify_text_comments'] ) ) {
			$text = $this->linkify_text( $text );
		}

		return $text;
	}

	/**
	 * Perform text linkification.
	 *
	 * @param  string $text Text to be processed for text linkification.
	 * @return string Text with replacements already processed.
	 */
	public function linkify_text( $text ) {
		$options         = $this->get_options();
		$text_to_link    = apply_filters( 'c2c_linkify_text',                $options['text_to_link'] );
		$case_sensitive  = apply_filters( 'c2c_linkify_text_case_sensitive', (bool) $options['case_sensitive'] );
		$limit           = apply_filters( 'c2c_linkify_text_replace_once',   (bool) $options['replace_once'] ) === true ? 1 : -1;
		$preg_flags      = $case_sensitive ? 's' : 'si';
		$mb_regex_encoding = null;

		$text = ' ' . $text . ' ';

		$can_do_mb = function_exists( 'mb_regex_encoding' ) && function_exists( 'mb_ereg_replace' ) && function_exists( 'mb_strlen' );

		if ( $text_to_link ) {

			// Store original mb_regex_encoding and then set it to UTF-8.
			if ( $can_do_mb ) {
				$mb_regex_encoding = mb_regex_encoding();
				mb_regex_encoding( 'UTF-8' );
			}

			// Sort array descending by key length. This way longer, more precise
			// strings take precedence over shorter strings, preventing premature
			// partial linking.
			// E.g. if "abc" and "abc def" are both defined for linking and in that
			// order, the string "abc def ghi" would match on "abc def", the longer
			// string rather than the shorter, less precise "abc".
			$keys = array_map( 'mb_strlen', array_keys( $text_to_link ) );
			array_multisort( $keys, SORT_DESC, $text_to_link );

			foreach ( $text_to_link as $old_text => $link ) {
				// If the link starts with a colon, treat it as a special shortcut to the
				// link for the referenced term. Nested referencing is not supported.
				if ( ':' === $link[0] ) {
					$link = $text_to_link[ substr( $link, 1 ) ];
				}

				// If link is empty, or is another term reference, don't linkify.
				if ( ! $link || ':' === $link[0] ) {
					continue;
				}

				// If the link does not contain a protocol and isn't absolute, prepend 'http://'
				// Sorry, not supporting non-root relative paths.
				if ( false === strpos( $link, '://' ) && ! path_is_absolute( $link ) ) {
					// Quick and rough check that the link looks like a link to prevent user
					// making invalid link. A period is sufficient to denote a file or domain.
					if ( false === strpos( $link, '.' ) ) {
						continue;
					}
					$link = 'http://' . $link;
				}

				$new_text = '<a href="' . esc_url( $link ) . '">\\1</a>';
				$new_text = apply_filters( 'c2c_linkify_text_linked_text', $new_text, $old_text, $link, $text_to_link );

				// Escape user-provided string from having regex characters.
				$old_text = preg_quote( $old_text, '~' );

				// If the string to be linked includes '&', consider '&amp;' and '&#038;' equivalents.
				// Visual editor will convert the former, but users aren't aware of the conversion.
				if ( false !== strpos( $old_text, '&' ) ) {
					$old_text = str_replace( '&', '&(amp;|#038;)?', $old_text );
				}

				// Regex to find text to replace, but not when in HTML tags or shortcodes.
				$regex = "(?![<\[].*)\b({$old_text})\b(?![^<>\[\]]*?[\]>])";

				// If the text to be replaced has multibyte character(s), use
				// mb_ereg_replace() if possible.
				if ( $can_do_mb && ( strlen( $old_text ) != mb_strlen( $old_text ) ) ) {
					// NOTE: mb_ereg_replace() does not support limiting the number of
					// replacements, hence the different handling if replacing once.
					if ( 1 === $limit ) {
						// Find first occurrence of the search string.
						$pos = mb_strpos( $text, $old_text );
						// Only do the replacement if the search string was found.
						if ( false !== $pos ) {
							$text  = mb_substr( $text, 0, $pos )
								. sprintf( str_replace( "\\1", '%s', $new_text ), $old_text )
								. mb_substr( $text, $pos + mb_strlen( $old_text ) );
						}
					} else {
						$text = mb_ereg_replace( $regex, $new_text, $text, $preg_flags );
					}
				} else {
					$text = preg_replace( "~{$regex}~{$preg_flags}", $new_text, $text, $limit );
				}
			}

			// Restore original mb_regexp_encoding, if changed.
			if ( $mb_regex_encoding ) {
				mb_regex_encoding( $mb_regex_encoding );
			}

			// Remove links within links.
			$text = preg_replace( "#(<a [^>]+>)(.*)<a [^>]+>([^<]*)</a>([^>]*)</a>#iU", "$1$2$3$4</a>" , $text );

		}

		return trim( $text );
	}

} // end c2c_LinkifyText

c2c_LinkifyText::get_instance();

endif; // end if !class_exists()
