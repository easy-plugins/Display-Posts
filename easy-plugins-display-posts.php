<?php
/**
 * @package   Easy Plugins: Display Posts
 * @author    Steven A. Zahm
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @link      https://connections-pro.com
 * @copyright 2019 Steven A. Zahm
 *
 * Plugin Name:       Easy Plugins: Display Posts
 * Plugin URI:        https://connections-pro.com
 * Description:       Display a listing of posts using the [display-posts] shortcode.
 * Version:           1.0
 * Author:            Steven A. Zahm and Bill Erickson
 * Author URI:        https://www.connections-pro.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       easy-plugins-display-posts
 * Domain Path:       /languages
 */

add_shortcode( 'display-posts', 'ezp_display_posts' );
/**
 * Callback for the display-posts shortcode.
 *
 * To customize, use the following filters: https://displayposts.com/docs/filters/
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function ezp_display_posts( $atts ) {

	/**
	 * Short circuit filter.
	 *
	 * Use this filter to return from this function immediately, with the return of the filter callback.
	 *
	 * @since 1.0
	 *
	 * @param bool  $short_circuit False to allow this function to continue, anything else to return that value.
	 * @param array $atts          Shortcode attributes.
	 */
	$output = apply_filters_deprecated( 'pre_display_posts_shortcode_output', array( FALSE ), '1.0', 'Easy_Plugins/Display_Posts/Render' );
	$output = apply_filters( 'Easy_Plugins/Display_Posts/Render', $output, $atts );

	if ( false !== $output ) {
		return $output;
	}

	// Original attributes, for filters.
	$original_atts = $atts;

	// Pull in shortcode attributes and set defaults.
	$atts = shortcode_atts(
		array(
			'author'                => '',
			'author_id'             => '',
			'category'              => '',
			'category_display'      => '',
			'category_id'           => false,
			'category_label'        => 'Posted in: ',
			'content_class'         => 'content',
			'date_format'           => '(n/j/Y)',
			'date'                  => '',
			'date_column'           => 'post_date',
			'date_compare'          => '=',
			'date_query_before'     => '',
			'date_query_after'      => '',
			'date_query_column'     => '',
			'date_query_compare'    => '',
			'display_posts_off'     => false,
			'excerpt_length'        => false,
			'excerpt_more'          => false,
			'excerpt_more_link'     => false,
			'exclude'               => false,
			'exclude_current'       => false,
			'has_password'          => null,
			'id'                    => false,
			'ignore_sticky_posts'   => false,
			'image_size'            => false,
			'include_author'        => false,
			'include_content'       => false,
			'include_date'          => false,
			'include_date_modified' => false,
			'include_excerpt'       => false,
			'include_excerpt_dash'  => true,
			'include_link'          => true,
			'include_title'         => true,
			'meta_key'              => '', // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_key
			'meta_value'            => '', // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_value
			'no_posts_message'      => '',
			'offset'                => 0,
			'order'                 => 'DESC',
			'orderby'               => 'date',
			'post_parent'           => false,
			'post_parent__in'       => false,
			'post_parent__not_in'   => false,
			'post_status'           => 'publish',
			'post_type'             => 'post',
			'posts_per_page'        => '10',
			's'                     => false,
			'tag'                   => '',
			'tax_operator'          => 'IN',
			'tax_include_children'  => true,
			'tax_term'              => false,
			'taxonomy'              => false,
			'time'                  => '',
			'title'                 => '',
			'wrapper'               => 'ul',
			'wrapper_class'         => 'display-posts-listing',
			'wrapper_id'            => false,
		),
		$atts,
		'display-posts'
	);

	// End early if shortcode should be turned off.
	if ( $atts['display_posts_off'] ) {
		return;
	}

	$author                = sanitize_text_field( $atts['author'] );
	$author_id             = (int) $atts['author_id'];
	$category              = sanitize_text_field( $atts['category'] );
	$category_display      = 'true' === $atts['category_display'] ? 'category' : sanitize_text_field( $atts['category_display'] );
	$category_id           = (int) $atts['category_id'];
	$category_label        = sanitize_text_field( $atts['category_label'] );
	$content_class         = array_map( 'sanitize_html_class', explode( ' ', $atts['content_class'] ) );
	$date_format           = sanitize_text_field( $atts['date_format'] );
	$date                  = sanitize_text_field( $atts['date'] );
	$date_column           = sanitize_text_field( $atts['date_column'] );
	$date_compare          = sanitize_text_field( $atts['date_compare'] );
	$date_query_before     = sanitize_text_field( $atts['date_query_before'] );
	$date_query_after      = sanitize_text_field( $atts['date_query_after'] );
	$date_query_column     = sanitize_text_field( $atts['date_query_column'] );
	$date_query_compare    = sanitize_text_field( $atts['date_query_compare'] );
	$excerpt_length        = (int) $atts['excerpt_length'];
	$excerpt_more          = sanitize_text_field( $atts['excerpt_more'] );
	$excerpt_more_link     = filter_var( $atts['excerpt_more_link'], FILTER_VALIDATE_BOOLEAN );
	$exclude               = $atts['exclude']; // Sanitized later as an array of integers.
	$exclude_current       = filter_var( $atts['exclude_current'], FILTER_VALIDATE_BOOLEAN );
	$has_password          = null !== $atts['has_password'] ? filter_var( $atts['has_password'], FILTER_VALIDATE_BOOLEAN ) : null;
	$id                    = $atts['id']; // Sanitized later as an array of integers.
	$ignore_sticky_posts   = filter_var( $atts['ignore_sticky_posts'], FILTER_VALIDATE_BOOLEAN );
	$image_size            = sanitize_key( $atts['image_size'] );
	$include_title         = filter_var( $atts['include_title'], FILTER_VALIDATE_BOOLEAN );
	$include_author        = filter_var( $atts['include_author'], FILTER_VALIDATE_BOOLEAN );
	$include_content       = filter_var( $atts['include_content'], FILTER_VALIDATE_BOOLEAN );
	$include_date          = filter_var( $atts['include_date'], FILTER_VALIDATE_BOOLEAN );
	$include_date_modified = filter_var( $atts['include_date_modified'], FILTER_VALIDATE_BOOLEAN );
	$include_excerpt       = filter_var( $atts['include_excerpt'], FILTER_VALIDATE_BOOLEAN );
	$include_excerpt_dash  = filter_var( $atts['include_excerpt_dash'], FILTER_VALIDATE_BOOLEAN );
	$include_link          = filter_var( $atts['include_link'], FILTER_VALIDATE_BOOLEAN );
	$meta_key              = sanitize_text_field( $atts['meta_key'] );
	$meta_value            = sanitize_text_field( $atts['meta_value'] );
	$no_posts_message      = sanitize_text_field( $atts['no_posts_message'] );
	$offset                = (int) $atts['offset'];
	$order                 = sanitize_key( $atts['order'] );
	$orderby               = sanitize_key( $atts['orderby'] );
	$post_parent           = $atts['post_parent']; // Validated later, after check for 'current'.
	$post_parent__in       = $atts['post_parent__in'];
	$post_parent__not_in   = $atts['post_parent__not_in'];
	$post_status           = $atts['post_status']; // Validated later as one of a few values.
	$post_type             = sanitize_text_field( $atts['post_type'] );
	$posts_per_page        = (int) $atts['posts_per_page'];
	$s                     = sanitize_text_field( $atts['s'] );
	$tag                   = sanitize_text_field( $atts['tag'] );
	$tax_operator          = $atts['tax_operator']; // Validated later as one of a few values.
	$tax_include_children  = filter_var( $atts['tax_include_children'], FILTER_VALIDATE_BOOLEAN );
	$tax_term              = sanitize_text_field( $atts['tax_term'] );
	$taxonomy              = sanitize_key( $atts['taxonomy'] );
	$time                  = sanitize_text_field( $atts['time'] );
	$shortcode_title       = sanitize_text_field( $atts['title'] );
	$wrapper               = sanitize_text_field( $atts['wrapper'] );
	$wrapper_class         = array_map( 'sanitize_html_class', explode( ' ', $atts['wrapper_class'] ) );

	if ( ! empty( $wrapper_class ) ) {
		$wrapper_class = ' class="' . implode( ' ', $wrapper_class ) . '"';
	}
	$wrapper_id = sanitize_html_class( $atts['wrapper_id'] );
	if ( ! empty( $wrapper_id ) ) {
		$wrapper_id = ' id="' . $wrapper_id . '"';
	}

	// Set up initial query for post.
	$args = array(
		'perm' => 'readable',
	);

	// Add args if they aren't empty.
	if ( ! empty( $category_id ) ) {
		$args['cat'] = $category_id;
	}
	if ( ! empty( $category ) ) {
		$args['category_name'] = $category;
	}
	if ( ! empty( $order ) ) {
		$args['order'] = $order;
	}
	if ( ! empty( $orderby ) ) {
		$args['orderby'] = $orderby;
	}
	if ( ! empty( $post_type ) ) {
		$args['post_type'] = ezp_display_posts_explode( $post_type );
	}
	if ( ! empty( $posts_per_page ) ) {
		$args['posts_per_page'] = $posts_per_page;
	}
	if ( ! empty( $s ) ) {
		$args['s'] = $s;
	}
	if ( ! empty( $tag ) ) {
		$args['tag'] = $tag;
	}

	// Date query.
	if ( ! empty( $date ) || ! empty( $time ) || ! empty( $date_query_after ) || ! empty( $date_query_before ) ) {
		$initial_date_query = array();
		$date_query_top_lvl = array();

		$valid_date_columns = array(
			'post_date',
			'post_date_gmt',
			'post_modified',
			'post_modified_gmt',
			'comment_date',
			'comment_date_gmt',
		);

		$valid_compare_ops = array( '=', '!=', '>', '>=', '<', '<=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' );

		// Sanitize and add date segments.
		$dates = ezp_display_posts_sanitize_date_time( $date );
		if ( ! empty( $dates ) ) {
			if ( is_string( $dates ) ) {
				$timestamp = strtotime( $dates );
				$dates     = array(
					'year'  => date( 'Y', $timestamp ),
					'month' => date( 'm', $timestamp ),
					'day'   => date( 'd', $timestamp ),
				);
			}
			foreach ( $dates as $arg => $segment ) {
				$initial_date_query[ $arg ] = $segment;
			}
		}

		// Sanitize and add time segments.
		$times = ezp_display_posts_sanitize_date_time( $time, 'time' );
		if ( ! empty( $times ) ) {
			foreach ( $times as $arg => $segment ) {
				$initial_date_query[ $arg ] = $segment;
			}
		}

		// Date query 'before' argument.
		$before = ezp_display_posts_sanitize_date_time( $date_query_before, 'date', true );
		if ( ! empty( $before ) ) {
			$initial_date_query['before'] = $before;
		}

		// Date query 'after' argument.
		$after = ezp_display_posts_sanitize_date_time( $date_query_after, 'date', true );
		if ( ! empty( $after ) ) {
			$initial_date_query['after'] = $after;
		}

		// Date query 'column' argument.
		if ( ! empty( $date_query_column ) && in_array( $date_query_column, $valid_date_columns, true ) ) {
			$initial_date_query['column'] = $date_query_column;
		}

		// Date query 'compare' argument.
		if ( ! empty( $date_query_compare ) && in_array( $date_query_compare, $valid_compare_ops, true ) ) {
			$initial_date_query['compare'] = $date_query_compare;
		}

		// Top-level date_query arguments. Only valid arguments will be added.
		//
		// 'column' argument.
		if ( ! empty( $date_column ) && in_array( $date_column, $valid_date_columns, true ) ) {
			$date_query_top_lvl['column'] = $date_column;
		}

		// 'compare' argument.
		if ( ! empty( $date_compare ) && in_array( $date_compare, $valid_compare_ops, true ) ) {
			$date_query_top_lvl['compare'] = $date_compare;
		}

		// Bring in the initial date query.
		if ( ! empty( $initial_date_query ) ) {
			$date_query_top_lvl[] = $initial_date_query;
		}

		// Date queries.
		$args['date_query'] = $date_query_top_lvl;
	}

	// Ignore Sticky Posts.
	if ( $ignore_sticky_posts ) {
		$args['ignore_sticky_posts'] = true;
	}

	// Password protected content.
	if ( null !== $has_password ) {
		$args['has_password'] = $has_password;
	}

	// Meta key (for ordering).
	if ( ! empty( $meta_key ) ) {
		$args['meta_key'] = $meta_key; // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_key
	}

	// Meta value (for simple meta queries).
	if ( ! empty( $meta_value ) ) {
		$args['meta_value'] = $meta_value; // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_value
	}

	// If Post IDs.
	if ( $id ) {
		$posts_in         = array_map( 'intval', ezp_display_posts_explode( $id ) );
		$args['post__in'] = $posts_in;
	}

	// If Exclude.
	$post__not_in = array();
	if ( ! empty( $exclude ) ) {
		$post__not_in = array_map( 'intval', ezp_display_posts_explode( $exclude ) );
	}
	if ( is_singular() && $exclude_current ) {
		$post__not_in[] = get_the_ID();
	}
	if ( ! empty( $post__not_in ) ) {
		$args['post__not_in'] = $post__not_in; // phpcs:ignore WordPressVIPMinimum.VIP.WPQueryParams.post__not_in
	}

	// Post Author.
	if ( ! empty( $author ) ) {
		if ( 'current' === $author && is_user_logged_in() ) {
			$args['author_name'] = wp_get_current_user()->user_login;
		} elseif ( 'current' === $author ) {
			$args['meta_key'] = 'dps_no_results'; // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_key
		} else {
			$args['author_name'] = $author;
		}
	} elseif ( ! empty( $author_id ) ) {
		$args['author'] = $author_id;
	}

	// Offset.
	if ( ! empty( $offset ) ) {
		$args['offset'] = $offset;
	}

	// Post Status.
	$post_status = ezp_display_posts_explode( $post_status );
	$validated   = array();
	$available   = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash', 'any' );
	foreach ( $post_status as $unvalidated ) {
		if ( in_array( $unvalidated, $available, true ) ) {
			$validated[] = $unvalidated;
		}
	}
	if ( ! empty( $validated ) ) {
		$args['post_status'] = $validated;
	}

	// If taxonomy attributes, create a taxonomy query.
	if ( ! empty( $taxonomy ) && ! empty( $tax_term ) ) {

		if ( 'current' === $tax_term ) {
			global $post;
			$terms    = wp_get_post_terms( get_the_ID(), $taxonomy );
			$tax_term = array();
			foreach ( $terms as $term ) {
				$tax_term[] = $term->slug;
			}
		} else {
			// Term string to array.
			$tax_term = ezp_display_posts_explode( $tax_term );
		}

		// Validate operator.
		if ( ! in_array( $tax_operator, array( 'IN', 'NOT IN', 'AND' ), true ) ) {
			$tax_operator = 'IN';
		}

		$tax_args = array(
			'tax_query' => array( // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy'         => $taxonomy,
					'field'            => 'slug',
					'terms'            => $tax_term,
					'operator'         => $tax_operator,
					'include_children' => $tax_include_children,
				),
			),
		);

		// Check for multiple taxonomy queries.
		$count            = 2;
		$more_tax_queries = false;
		while (
			isset( $original_atts[ 'taxonomy_' . $count ] ) && ! empty( $original_atts[ 'taxonomy_' . $count ] ) &&
			isset( $original_atts[ 'tax_' . $count . '_term' ] ) && ! empty( $original_atts[ 'tax_' . $count . '_term' ] )
		) :

			// Sanitize values.
			$more_tax_queries     = true;
			$taxonomy             = sanitize_key( $original_atts[ 'taxonomy_' . $count ] );
			$terms                = ezp_display_posts_explode( sanitize_text_field( $original_atts[ 'tax_' . $count . '_term' ] ) );
			$tax_operator         = isset( $original_atts[ 'tax_' . $count . '_operator' ] ) ? $original_atts[ 'tax_' . $count . '_operator' ] : 'IN';
			$tax_operator         = in_array( $tax_operator, array( 'IN', 'NOT IN', 'AND' ), true ) ? $tax_operator : 'IN';
			$tax_include_children = isset( $original_atts[ 'tax_' . $count . '_include_children' ] ) ? filter_var( $atts[ 'tax_' . $count . '_include_children' ], FILTER_VALIDATE_BOOLEAN ) : true;

			$tax_args['tax_query'][] = array(
				'taxonomy'         => $taxonomy,
				'field'            => 'slug',
				'terms'            => $terms,
				'operator'         => $tax_operator,
				'include_children' => $tax_include_children,
			);

			$count++;

		endwhile;

		if ( $more_tax_queries ) :
			$tax_relation = 'AND';
			if ( isset( $original_atts['tax_relation'] ) && in_array( $original_atts['tax_relation'], array( 'AND', 'OR' ), true ) ) {
				$tax_relation = $original_atts['tax_relation'];
			}
			$args['tax_query']['relation'] = $tax_relation;
		endif;

		$args = array_merge_recursive( $args, $tax_args );
	}

	// If post parent attribute, set up parent.
	if ( false !== $post_parent ) {
		if ( 'current' === $post_parent ) {
			$post_parent = get_the_ID();
		}
		$args['post_parent'] = (int) $post_parent;
	}

	if ( false !== $post_parent__in ) {
		$args['post_parent__in'] = array_map( 'intval', ezp_display_posts_explode( $atts['post_parent__in'] ) );
	}
	if ( false !== $post_parent__not_in ) {
		$args['post_parent__not_in'] = array_map( 'intval', ezp_display_posts_explode( $atts['post_parent__in'] ) );
	}

	// Set up html elements used to wrap the posts.
	// Default is ul/li, but can also be ol/li and div/div.
	$wrapper_options = array( 'ul', 'ol', 'div' );
	if ( ! in_array( $wrapper, $wrapper_options, true ) ) {
		$wrapper = 'ul';
	}
	$inner_wrapper = 'div' === $wrapper ? 'div' : 'li';

	/**
	 * Filter the arguments passed to WP_Query.
	 *
	 * @since 1.0
	 *
	 * @param array $args          Parsed arguments to pass to WP_Query.
	 * @param array $original_atts Original attributes passed to the shortcode.
	 */
	global $dps_listing;

	$args = apply_filters_deprecated( 'display_posts_shortcode_args', array( $args, $original_atts ), '1.0', 'Easy_Plugins/Display_Posts/Query/Args' );
	$args = apply_filters( 'Easy_Plugins/Display_Posts/Query/Args', $args, $original_atts );

	$dps_listing = new WP_Query( $args );

	if ( ! $dps_listing->have_posts() ) {

		$no_posts_message = wpautop( $no_posts_message );

		/**
		 * Filter content to display if no posts match the current query.
		 *
		 * @since 1.0
		 *
		 * @param string $no_posts_message Content to display, returned via {@see wpautop()}.
		 */
		$no_posts_message = apply_filters_deprecated( 'display_posts_shortcode_args', array( $no_posts_message ), '1.0', 'Easy_Plugins/Display_Posts/No_Results' );
		$no_posts_message = apply_filters( 'Easy_Plugins/Display_Posts/No_Results', $no_posts_message );

		return $no_posts_message;
	}

	$inner = '';
	while ( $dps_listing->have_posts() ) :
		$dps_listing->the_post();
		global $post;

		$image   = '';
		$date    = '';
		$author  = '';
		$excerpt = '';
		$content = '';

		if ( $include_title && $include_link ) {
			/** This filter is documented in wp-includes/link-template.php */
			$title = '<a class="title" href="' . apply_filters( 'the_permalink', get_permalink() ) . '">' . get_the_title() . '</a>';

		} elseif ( $include_title ) {
			$title = '<span class="title">' . get_the_title() . '</span>';

		} else {
			$title = '';
		}

		if ( $image_size && has_post_thumbnail() && $include_link ) {
			$image = '<a class="image" href="' . get_permalink() . '">' . get_the_post_thumbnail( get_the_ID(), $image_size ) . '</a> ';

		} elseif ( $image_size && has_post_thumbnail() ) {
			$image = '<span class="image">' . get_the_post_thumbnail( get_the_ID(), $image_size ) . '</span> ';

		}

		if ( $include_date ) {
			$date = 'relative' === $date_format ? ezp_display_posts_relative_date( get_the_date( 'U' ) ) : get_the_date( $date_format );
		} elseif ( $include_date_modified ) {
			$date = 'relative' === $date_format ? ezp_display_posts_relative_date( get_the_modified_time( 'U' ) ) : get_the_modified_date( $date_format );
		}
		if ( ! empty( $date ) ) {
			$date = ' <span class="date">' . $date . '</span>';
		}

		if ( $include_author ) {

			$author = ' <span class="author">by ' . get_the_author() . '</span>';

			/**
			 * Filter the HTML markup to display author information for the current post.
			 *
			 * @since 1.0
			 *
			 * @param string $author_output HTML markup to display author information.
			 */
			$author = apply_filters_deprecated( 'display_posts_shortcode_author', array( $author, $original_atts ), '1.0', 'Easy_Plugins/Display_Posts/Post/Author' );
			$author = apply_filters( 'Easy_Plugins/Display_Posts/Post/Author', $author, $original_atts );
		}

		if ( $include_excerpt ) {

			// Custom build excerpt based on shortcode parameters.
			if ( $excerpt_length || $excerpt_more || $excerpt_more_link ) {

				$length = $excerpt_length ? $excerpt_length : apply_filters( 'excerpt_length', 55 );
				$more   = $excerpt_more ? $excerpt_more : apply_filters( 'excerpt_more', '' );
				$more   = $excerpt_more_link ? ' <a class="excerpt-more" href="' . get_permalink() . '">' . $more . '</a>' : ' <span class="excerpt-more">' . $more . '</span>';

				$full_manual_excerpt = apply_filters_deprecated( 'display_posts_shortcode_full_manual_excerpt', array( FALSE ), '1.0', 'Easy_Plugins/Display_Posts/Post/Manual_Excerpt' );
				$full_manual_excerpt = apply_filters( 'Easy_Plugins/Display_Posts/Post/Manual_Excerpt', $full_manual_excerpt );

				if ( has_excerpt() && $full_manual_excerpt ) {
					$excerpt = $post->post_excerpt . $more;
				} elseif ( has_excerpt() ) {
					$excerpt = wp_trim_words( strip_shortcodes( $post->post_excerpt ), $length ) . $more;
				} else {
					$excerpt = wp_trim_words( strip_shortcodes( $post->post_content ), $length ) . $more;
				}

				// Use default, can customize with WP filters.
			} else {
				$excerpt = get_the_excerpt();
			}

			if ( ! empty( $excerpt ) ) {

				$excerpt = ' <span class="excerpt">' . $excerpt . '</span>';
				if ( $include_excerpt_dash ) {
					$excerpt = ' <span class="excerpt-dash">-</span>' . $excerpt;
				}
			}
		}

		if ( $include_content ) {
			add_filter( 'shortcode_atts_display-posts', 'ezp_display_posts_off', 10, 3 );
			/** This filter is documented in wp-includes/post-template.php */
			$content = '<div class="' . implode( ' ', $content_class ) . '">' . apply_filters( 'the_content', get_the_content() ) . '</div>';
			remove_filter( 'shortcode_atts_display-posts', 'ezp_display_posts_off', 10, 3 );
		}

		// Display categories the post is in.
		$category_display_text = '';

		if ( $category_display && is_object_in_taxonomy( get_post_type(), $category_display ) ) {
			$terms       = get_the_terms( get_the_ID(), $category_display );
			$term_output = array();

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$term_output[] = '<a href="' . get_term_link( $term, $category_display ) . '">' . $term->name . '</a>';
				}
				$category_display_text = ' <span class="category-display"><span class="category-display-label">' . $category_label . '</span> ' . implode( ', ', $term_output ) . '</span>';
			}

			/**
			 * Filter the list of categories attached to the current post.
			 *
			 * @since 1.0
			 *
			 * @param string   $category_display Current Category Display text
			 */
			$category_display_text = apply_filters_deprecated( 'display_posts_shortcode_category_display', array( $category_display_text, $terms, $category_display, $original_atts ), '1.0', 'Easy_Plugins/Display_Posts/Post/Categories' );
			$category_display_text = apply_filters( 'Easy_Plugins/Display_Posts/Post/Categories', $category_display_text, $terms, $category_display, $original_atts );
		}

		$class = array( 'listing-item' );

		/**
		 * Filter the post classes for the inner wrapper element of the current post.
		 *
		 * @since 1.0
		 *
		 * @param array    $class         Post classes.
		 * @param WP_Post  $post          Post object.
		 * @param WP_Query $dps_listing       WP_Query object for the posts listing.
		 * @param array    $original_atts Original attributes passed to the shortcode.
		 */
		$class = apply_filters_deprecated( 'display_posts_shortcode_post_class', array( $class, $post, $dps_listing, $original_atts ), '1.0', 'Easy_Plugins/Display_Posts/Post/Class' );
		$class = apply_filters( 'Easy_Plugins/Display_Posts/Post/Class', $class, $post, $dps_listing, $original_atts );

		$class  = array_map( 'sanitize_html_class', $class );
		$output = '<' . $inner_wrapper . ' class="' . implode( ' ', $class ) . '">' . $image . $title . $date . $author . $category_display_text . $excerpt . $content . '</' . $inner_wrapper . '>';

		/**
		 * Filter the HTML markup for output via the shortcode.
		 *
		 * @since 1.0
		 *
		 * @param string $output        The shortcode's HTML output.
		 * @param array  $original_atts Original attributes passed to the shortcode.
		 * @param string $image         HTML markup for the post's featured image element.
		 * @param string $title         HTML markup for the post's title element.
		 * @param string $date          HTML markup for the post's date element.
		 * @param string $excerpt       HTML markup for the post's excerpt element.
		 * @param string $inner_wrapper Type of container to use for the post's inner wrapper element.
		 * @param string $content       The post's content.
		 * @param string $class         Space-separated list of post classes to supply to the $inner_wrapper element.
		 * @param string $author        HTML markup for the post's author.
		 * @param string $category_display_text
		 */
		$output = apply_filters_deprecated( 'display_posts_shortcode_output', array( $output, $original_atts, $image, $title, $date, $excerpt, $inner_wrapper, $content, $class, $author, $category_display_text ), '1.0', 'Easy_Plugins/Display_Posts/Post/HTML' );
		$inner .= apply_filters( 'Easy_Plugins/Display_Posts/Post/HTML', $output, $original_atts, $image, $title, $date, $excerpt, $inner_wrapper, $content, $class, $author, $category_display_text );

	endwhile;
	wp_reset_postdata();

	/**
	 * Filter the shortcode output's opening outer wrapper element.
	 *
	 * @since 1.0
	 *
	 * @param string $wrapper_open  HTML markup for the opening outer wrapper element.
	 * @param array  $original_atts Original attributes passed to the shortcode.
	 * @param object $dps_listing, WP Query object
	 */
	$open = apply_filters_deprecated( 'display_posts_shortcode_wrapper_open', array( '<' . $wrapper . $wrapper_class . $wrapper_id . '>', $original_atts, $dps_listing ), '1.0', 'Easy_Plugins/Display_Posts/HTML/Wrap_Open' );
	$open = apply_filters( 'Easy_Plugins/Display_Posts/HTML/Wrap_Open', $open, $original_atts, $dps_listing );

	/**
	 * Filter the shortcode output's closing outer wrapper element.
	 *
	 * @since 1.0
	 *
	 * @param string $wrapper_close HTML markup for the closing outer wrapper element.
	 * @param array  $original_atts Original attributes passed to the shortcode.
	 * @param object $dps_listing, WP Query object
	 */
	$close = apply_filters_deprecated( 'display_posts_shortcode_wrapper_close', array( '</' . $wrapper . '>', $original_atts, $dps_listing ), '1.0', 'Easy_Plugins/Display_Posts/HTML/Wrap_Close' );
	$close = apply_filters( 'Easy_Plugins/Display_Posts/HTML/Wrap_Close', $close, $original_atts, $dps_listing );

	$return = '';

	if ( $shortcode_title ) {

		/**
		 * Filter the shortcode output title tag element.
		 *
		 * @since 1.0
		 *
		 * @param string $tag           Type of element to use for the output title tag. Default 'h2'.
		 * @param array  $original_atts Original attributes passed to the shortcode.
		 */
		$title_tag = apply_filters_deprecated( 'display_posts_shortcode_title_tag', array( 'h2', $original_atts ), '1.0', 'Easy_Plugins/Display_Posts/Posts/HTML/Title_Tag' );
		$title_tag = apply_filters( 'Easy_Plugins/Display_Posts/Posts/HTML/Title_Tag', $title_tag, $original_atts );

		$return .= '<' . $title_tag . ' class="display-posts-title">' . $shortcode_title . '</' . $title_tag . '>' . "\n";
	}

	$return .= $open . $inner . $close;

	return $return;
}

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
function ezp_display_posts_sanitize_date_time( $date_time, $type = 'date', $accepts_string = false ) {
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

	$parts = array_map( 'absint', explode( 'date' === $type ? '-' : ':', $date_time ) );

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
 * Turn off display posts shortcode.
 *
 * If display full post content, any uses of [display-posts] are disabled.
 *
 * @param array $out   Returned shortcode values.
 * @param array $pairs List of supported attributes and their defaults.
 * @param array $atts  Original shortcode attributes.
 * @return array
 */
function ezp_display_posts_off( $out, $pairs, $atts ) {

	$off = TRUE;

	/**
	 * Filter whether to disable the display-posts shortcode.
	 *
	 * The function and filter were added for backward-compatibility with
	 * 2.3 behavior in certain circumstances.
	 *
	 * @since 1.0
	 *
	 * @param bool $disable Whether to disable the display-posts shortcode. Default true.
	 */
	$off = apply_filters_deprecated( 'display_posts_shortcode_inception_override', array( $off ), '1.0', 'Easy_Plugins/Display_Posts/Inception' );
	$off = apply_filters( 'Easy_Plugins/Display_Posts/Inception', $off );

	$out['display_posts_off'] = $off;

	return $out;
}

/**
 * Explode list using "," and ", ".
 *
 * @param string $string String to split up.
 * @return array Array of string parts.
 */
function ezp_display_posts_explode( $string = '' ) {
	$string = str_replace( ', ', ',', $string );
	return explode( ',', $string );
}

/**
 * Get a relative data with "ago" suffix.
 *
 * @param int $date Unix timestamp.
 * @return string Human readable time difference.
 */
function ezp_display_posts_relative_date( $date ) {
	return human_time_diff( $date ) . ' ' . __( 'ago', 'display-posts' );
}
