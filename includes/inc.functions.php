<?php

namespace Easy_Plugins\Functions;

/**
 * Sanitize the segments of a given date or time for a date query.
 *
 * Accepts times entered in the 'HH:MM:SS' or 'HH:MM' formats, and dates
 * entered in the 'YYYY-MM-DD' format.
 *
 * @param string $date_time      Date or time string to sanitize the parts of.
 * @param string $type           Optional. Type of value to sanitize. Accepts
 *                               'date' or 'time'. Default 'date'.
 * @param bool   $accepts_string Optional. Whether the return value accepts a string.
 *                               Default false.
 * @return array|string Array of valid date or time segments, a timestamp, otherwise
 *                      an empty array.
 */
function sanitize_date_time( $date_time, $type = 'date', $accepts_string = false ) {
	if ( empty( $date_time ) || ! in_array( $type, array( 'date', 'time' ), true ) ) {
		return array();
	}

	$segments = array();

	/*
	 * If $date_time is not a strictly-formatted date or time, attempt to salvage it with
	 * as strototime()-ready string. This is supported by the 'date', 'date_query_before',
	 * and 'date_query_after' attributes.
	 */
	if (
		true === $accepts_string
		&& ( false !== strpos( $date_time, ' ' ) || false === strpos( $date_time, '-' ) )
	) {
		$timestamp = strtotime( $date_time );
		if ( false !== $timestamp ) {
			return $date_time;
		}
	}

	$parts = array_map( 'absint', to_array( 'date' === $type ? '-' : ':', $date_time ) );

	// Date.
	if ( 'date' === $type ) {
		// Defaults to 2001 for years, January for months, and 1 for days.
		$year  = 1;
		$month = 1;
		$day   = 1;

		if ( count( $parts ) >= 3 ) {
			list( $year, $month, $day ) = $parts;

			$year  = ( $year >= 1 && $year <= 9999 ) ? $year : 1;
			$month = ( $month >= 1 && $month <= 12 ) ? $month : 1;
			$day   = ( $day >= 1 && $day <= 31 ) ? $day : 1;
		}

		$segments = array(
			'year'  => $year,
			'month' => $month,
			'day'   => $day,
		);

		// Time.
	} elseif ( 'time' === $type ) {
		// Defaults to 0 for all segments.
		$hour   = 0;
		$minute = 0;
		$second = 0;

		switch ( count( $parts ) ) {
			case 3:
				list( $hour, $minute, $second ) = $parts;
				$hour                           = ( $hour >= 0 && $hour <= 23 ) ? $hour : 0;
				$minute                         = ( $minute >= 0 && $minute <= 60 ) ? $minute : 0;
				$second                         = ( $second >= 0 && $second <= 60 ) ? $second : 0;
				break;
			case 2:
				list( $hour, $minute ) = $parts;
				$hour                  = ( $hour >= 0 && $hour <= 23 ) ? $hour : 0;
				$minute                = ( $minute >= 0 && $minute <= 60 ) ? $minute : 0;
				break;
			default:
				break;
		}

		$segments = array(
			'hour'   => $hour,
			'minute' => $minute,
			'second' => $second,
		);
	}

	/**
	 * Filter the sanitized segments for the given date or time string.
	 *
	 * @since 1.0
	 *
	 * @param array  $segments  Array of sanitized date or time segments, e.g. hour, minute, second,
	 *                          or year, month, day, depending on the value of the $type parameter.
	 * @param string $date_time Date or time string. Dates are formatted 'YYYY-MM-DD', and times are
	 *                          formatted 'HH:MM:SS' or 'HH:MM'.
	 * @param string $type      Type of string to sanitize. Can be either 'date' or 'time'.
	 */
	$segments = apply_filters_deprecated( 'display_posts_shortcode_sanitized_segments', array( $segments, $date_time, $type ), '1.0', 'Easy_Plugins/Display_Posts/Query/DateTime' );
	$segments = apply_filters( 'Easy_Plugins/Display_Posts/Query/DateTime', $segments, $date_time, $type );

	return $segments;
}

/**
 * Explode list using "," and ", ".
 *
 * @param string $string String to split up.
 * @return array Array of string parts.
 */
function to_array( $string = '' ) {
	$string = str_replace( ', ', ',', $string );
	return explode( ',', $string );
}

/**
 * Converts the following strings: yes/no; true/false and 0/1 to boolean values.
 * If the supplied string does not match one of those values the method will return NULL.
 *
 * @since 1.0
 *
 * @param string|int|bool $value
 *
 * @return bool
 */
function to_boolean( $value ) {

	// Already a bool, return it.
	if ( is_bool( $value ) ) return $value;

	$value = filter_var( strtolower( $value ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

	if ( is_null( $value ) ) {

		$value = FALSE;
	}

	return $value;
}
