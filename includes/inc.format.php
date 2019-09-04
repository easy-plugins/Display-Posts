<?php

namespace Easy_Plugins\Display_Posts\Formatting;

/**
 * Get a relative data with "ago" suffix.
 *
 * @param int $date Unix timestamp.
 * @return string Human readable time difference.
 */
function relative_date( int $date ) : string {

	return human_time_diff( $date ) . ' ' . __( 'ago', 'display-posts' );
}

