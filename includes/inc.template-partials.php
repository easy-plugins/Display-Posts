<?php

namespace Easy_Plugins\Display_Posts\Template\Partials;

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
