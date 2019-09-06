<?php

namespace Easy_Plugins\Display_Posts\Template\Partials;

/**
 * @since 1.0
 *
 * @param string $message
 *
 * @return string
 */
function no_posts_message( string $message ) : string {

	$message = wpautop( $message );

	/**
	 * Filter content to display if no posts match the current query.
	 *
	 * @since 1.0
	 *
	 * @param string $no_posts_message Content to display, returned via {@see wpautop()}.
	 */
	$message = apply_filters_deprecated( 'display_posts_shortcode_args', array( $message ), '1.0', 'Easy_Plugins/Display_Posts/No_Results' );
	$message = apply_filters( 'Easy_Plugins/Display_Posts/No_Results', $message );

	return $message;
}

/**
 * @since 1.0
 *
 * @param string $heading
 * @param array  $untrusted The user supplied shortcode options.
 *
 * @return string
 */
function posts_list_heading( string $heading, array $untrusted ) : string {

	$html = '';

	if ( 0 < strlen( $heading ) ) {

		/**
		 * Filter the shortcode output title tag element.
		 *
		 * @since 1.0
		 *
		 * @param string $tag       Type of element to use for the output title tag. Default 'h2'.
		 * @param array  $untrusted Original attributes passed to the shortcode.
		 */
		$title_tag = apply_filters_deprecated( 'display_posts_shortcode_title_tag', array( 'h2', $untrusted ), '1.0', 'Easy_Plugins/Display_Posts/Posts/HTML/Title_Tag' );
		$title_tag = apply_filters( 'Easy_Plugins/Display_Posts/Posts/HTML/Title_Tag', $title_tag, $untrusted );

		$html = '<' . $title_tag . ' class="display-posts-title">' . $heading . '</' . $title_tag . '>' . "\n";
	}

	return $html;
}
