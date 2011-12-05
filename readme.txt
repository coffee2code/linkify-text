=== Linkify Text ===
Contributors: coffee2code
Donate link: http://coffee2code.com/donate
Tags: text, link, hyperlink, autolink, replace, shortcut, shortcuts, post, post content, coffee2code
Requires at least: 3.1
Tested up to: 3.3
Stable tag: 1.0
Version: 1.0

Automatically hyperlink words or phrases in your posts.

== Description ==

Automatically hyperlink words or phrases in your posts.

This plugin allows you to define words or phrases that, whenever they appear in your posts or pages, get automatically linked to the URLs of your choosing.  For instance, wherever you may mention the word "WordPress", that can get automatically linked as "[WordPress](http://wordpress.org)".

Links: [Plugin Homepage](http://coffee2code.com/wp-plugins/linkify-text/) | [Author Homepage](http://coffee2code.com)


== Installation ==

1. Unzip `linkify-text.zip` inside the `/wp-content/plugins/` directory (or install via the built-in WordPress plugin installer)
1. Activate the plugin through the 'Plugins' admin menu in WordPress
1. (optional) Go to the `Settings` -> `Linkify Text` admin options page and define text and the URLs they should point to


== Frequently Asked Questions ==

= Does this plugin modify the post content in the database? =

No.  The plugin filters post content on-the-fly.

= Will this work for posts I wrote prior to installing this plugin? =

Yes, if they include strings that you've now defined with links.

= What post fields get handled by this plugin? =

By default, the plugin filters the post content, post excerpt fields, widget text, and optionally comments and comment excerpts.  You can use the 'c2c_linkify_text_filters' filter to modify that behavior (see Filters section).

= How can I get text linkification to apply for custom fields (or something not linkified by default)? =

You can add to the list of filters that get text linkified using something like this (added to your theme's functions.php file, for instance):

`
// Enable text linkification for custom fields
add_filter( 'c2c_linkify_text_filters', 'more_text_replacements' );
function more_text_replacements( $filters ) {
	$filters[] = 'the_meta'; // Here you could put in the name of any filter you want
	return $filters;
}
`

= Is the plugin case sensitive? =

By default, yes.  There is a setting you can change to make it case insensitive.  Or you can use the 'c2c_linkify_text_case_sensitive' filter (see Filters section).

= What if the word or phrase is already linked in a post? =

Already linked text will not get linked again by this plugin (regardless of what the link may be).


== Screenshots ==

1. A screenshot of the admin options page for the plugin, where you define the text and their related links


== Filters ==

The plugin exposes four filters for hooking.  Typically, the code to utilize these hooks would go inside your active theme's functions.php file.

= c2c_linkify_text_filters (filter) =

The 'c2c_linkify_text_filters' hook allows you to customize what hooks get text linkification applied to them.

Arguments:

* $hooks (array): Array of hooks that will be text linkified.

Example:

`
// Enable text linkification for custom fields
add_filter( 'c2c_linkify_text_filters', 'more_text_replacements' );
function more_text_replacements( $filters ) {
	$filters[] = 'the_meta'; // Here you could put in the name of any filter you want
	return $filters;
}
`

= c2c_linkify_text_comments (filter) =

The 'c2c_linkify_text_comments' hook allows you to customize or override the setting indicating if text linkification should be enabled in comments.

Arguments:

* $state (bool): Either true or false indicating if text linkification is enabled for comments.  The default value will be the value set via the plugin's settings page.

Example:

`// Prevent text linkification from ever being enabled in comments.
add_filter( 'c2c_linkify_text_comments', '__return_false' );`

= c2c_linkify_text (filter) =

The 'c2c_linkify_text' hook allows you to customize or override the setting defining all of the text phrases and their associated links.

Arguments:

* $linkify_text_array (array): Array of text and their associated links.  The default value will be the value set via the plugin's settings page.

Example:

`
// Add more text to be linked
add_filter( 'c2c_linkify_text', 'my_text_linkifications' );
function my_text_linkifications( $replacements ) {
	// Add text link
	$replacements['Matt Mullenweg'] => 'http://ma.tt';
	// Unset a text link that we never want defined
	if ( isset( $replacements['WordPress'] ) )
		unset( $replacements['WordPress'] );
	// Important!
	return $replacements;
}
`

= c2c_linkify_text_case_sensitive (filter) =

The 'c2c_linkify_text_case_sensitive' hook allows you to customize or override the setting indicating if text matching for potential text linkification should be case sensitive or not.

Arguments:

* $state (bool): Either true or false indicating if text matching is case sensitive.  The default value will be the value set via the plugin's settings page.

Example:

`// Prevent text matching from ever being case sensitive.
add_filter( 'c2c_linkify_text_case_sensitive', '__return_false' );`


== Changelog ==

= 1.0 =
* Initial release
