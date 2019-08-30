<?php

namespace Easy_Plugins\Formatting;

/**
 * Get a relative data with "ago" suffix.
 *
 * @param int $date Unix timestamp.
 * @return string Human readable time difference.
 */
function relative_date( $date ) {

	return human_time_diff( $date ) . ' ' . __( 'ago', 'display-posts' );
}

