<?php

namespace Easy_Plugins\Display_Posts\Shortcode;

use WP_Error;
use WP_Post;
use WP_Query;
use function Easy_Plugins\Display_Posts\Functions\{to_array, sanitize_date_time, to_boolean};
use function Easy_Plugins\Display_Posts\Formatting\{relative_date};

class Display_Posts {

	protected function __construct() {
		// Intentionally Left blank.
	}

	public static function defaults() {

		return array(
			'author'                => '',
			'author_id'             => '',
			'category'              => '',
			'category_display'      => '',
			'category_id'           => FALSE,
			'category_label'        => esc_html__( 'Posted in: ', 'easy-plugins-display-posts' ),
			'content_class'         => 'content',
			'date_format'           => '(n/j/Y)',
			'date'                  => '',
			'date_column'           => 'post_date',
			'date_compare'          => '=',
			'date_query_before'     => '',
			'date_query_after'      => '',
			'date_query_column'     => '',
			'date_query_compare'    => '',
			'display_posts_off'     => FALSE,
			'excerpt_length'        => FALSE,
			'excerpt_more'          => FALSE,
			'excerpt_more_link'     => FALSE,
			'exclude'               => FALSE,
			'exclude_current'       => FALSE,
			'has_password'          => NULL,
			'id'                    => FALSE,
			'ignore_sticky_posts'   => FALSE,
			'image_size'            => FALSE,
			'include_author'        => FALSE,
			'include_content'       => FALSE,
			'include_date'          => FALSE,
			'include_date_modified' => FALSE,
			'include_excerpt'       => FALSE,
			'include_excerpt_dash'  => TRUE,
			'include_link'          => TRUE,
			'include_title'         => TRUE,
			'meta_key'              => '', // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_key
			'meta_value'            => '', // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_value
			'no_posts_message'      => '',
			'offset'                => 0,
			'order'                 => 'DESC',
			'orderby'               => 'date',
			'post_parent'           => FALSE,
			'post_parent__in'       => FALSE,
			'post_parent__not_in'   => FALSE,
			'post_status'           => 'publish',
			'post_type'             => 'post',
			'posts_per_page'        => '10',
			's'                     => FALSE,
			'tag'                   => '',
			'tax_operator'          => 'IN',
			'tax_include_children'  => TRUE,
			'tax_term'              => FALSE,
			'taxonomy'              => FALSE,
			'time'                  => '',
			'title'                 => '',
			'wrapper'               => 'ul',
			'wrapper_class'         => 'display-posts-listing',
			'wrapper_id'            => FALSE,
		);
	}

	/**
	 * @param string $key
	 * @param array  $atts
	 *
	 * @return array|bool|int|string|WP_Error
	 */
	private static function get_option( $key, $atts ) {

		if ( ! in_array( $key, self::defaults() ) ) {

			return new WP_Error(
				'invalid option',
				esc_html__( 'Invalid should option called.', 'easy-plugins-display-posts' ),
				$key
			);
		}

		switch ( $key ) {

			case 'author':
			case 'category':
			case 'category_label':
			case 'date_format':
			case 'date':
			case 'date_column':
			case 'date_compare':
			case 'date_query_before':
			case 'date_query_after':
			case 'date_query_column':
			case 'date_query_compare':
			case 'excerpt_more':
			case 'meta_key':
			case 'meta_value':
			case 'no_posts_message':
			case 'post_type':
			case 's':
			case 'tag':
			case 'tax_term':
			case 'time':
			case 'title':
			case 'wrapper':

				$value = sanitize_text_field( $atts[ $key ] );
				break;

			case 'excerpt_more_link':
			case 'exclude_current':
			case 'ignore_sticky_posts':
			case 'include_title':
			case 'include_author':
			case 'include_content':
			case 'include_date':
			case 'include_date_modified':
			case 'include_excerpt':
			case 'include_excerpt_dash':
			case 'include_link':
			case 'tax_include_children':

				$value = to_boolean( $atts[ $key ] );
				break;

			case 'author_id':
			case 'category_id':
			case 'excerpt_length':
			case 'offset':
			case 'posts_per_page':

				$value = absint( $atts[ $key ] );
				break;

			case 'image_size':
			case 'order':
			case 'orderby':
			case 'taxonomy':

			$value = sanitize_key( $atts[ $key ] );
				break;

			case 'content_class':
			case 'wrapper_class':

				$value = array_map( 'sanitize_html_class', explode( ' ', $atts[ $key ] ) );
				break;

			default:

				$value = new WP_Error(
					'invalid option',
					esc_html__( 'Invalid should option called.', 'easy-plugins-display-posts' ),
					$key
				);
		}

		return $value;
	}

	/**
	 * Callback for the display-posts shortcode.
	 *
	 * To customize, use the following filters: https://displayposts.com/docs/filters/
	 *
	 * @param array  $atts    User supplied attributes.
	 * @param string $content The content enclosed within the shortcode.
	 * @param array  $tag     The shortcode tag.
	 *
	 * @return string
	 */
	public static function render( $atts, $content, $tag ) {

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
		$atts = shortcode_atts( self::defaults(), $atts, $tag );

		// End early if shortcode should be turned off.
		if ( $atts['display_posts_off'] ) {
			return '';
		}

		$author                = self::get_option( 'author', $atts );
		$author_id             = self::get_option( 'author_id', $atts );
		$category              = self::get_option( 'category', $atts );
		$category_display      = 'true' === $atts['category_display'] ? 'category' : sanitize_text_field( $atts['category_display'] );
		$category_id           = self::get_option( 'category_id', $atts );
		//$category_label        = self::get_option( 'category_label', $atts );
		//$content_class         = self::get_option( 'content_class', $atts );
		$date_format           = self::get_option( 'date_format', $atts );
		$date                  = self::get_option( 'date', $atts );
		$date_column           = self::get_option( 'date_column', $atts );
		$date_compare          = self::get_option( 'date_compare', $atts );
		$date_query_before     = self::get_option( 'date_query_before', $atts );
		$date_query_after      = self::get_option( 'date_query_after', $atts );
		$date_query_column     = self::get_option( 'date_query_column', $atts );
		$date_query_compare    = self::get_option( 'date_query_compare', $atts );
		$excerpt_length        = self::get_option( 'excerpt_length', $atts );
		$excerpt_more          = self::get_option( 'excerpt_more', $atts );
		$excerpt_more_link     = self::get_option( 'excerpt_more_link', $atts );
		$exclude               = $atts['exclude']; // Sanitized later as an array of integers.
		$exclude_current       = self::get_option( 'exclude_current', $atts );
		$has_password          = null !== $atts['has_password'] ? filter_var( $atts['has_password'], FILTER_VALIDATE_BOOLEAN ) : null;
		$id                    = $atts['id']; // Sanitized later as an array of integers.
		//$ignore_sticky_posts   = self::get_option( 'ignore_sticky_posts', $atts );
		$image_size            = self::get_option( 'image_size', $atts );
		$include_title         = self::get_option( 'include_title', $atts );
		//$include_author        = self::get_option( 'include_author', $atts );
		//$include_content       = self::get_option( 'include_content', $atts );
		//$include_date          = self::get_option( 'include_date', $atts );
		//$include_date_modified = self::get_option( 'include_date_modified', $atts );
		//$include_excerpt       = self::get_option( 'include_excerpt', $atts );
		//$include_excerpt_dash  = self::get_option( 'include_excerpt_dash', $atts );
		$include_link          = self::get_option( 'include_link', $atts );
		$meta_key              = self::get_option( 'meta_key', $atts );
		$meta_value            = self::get_option( 'meta_value', $atts );
		$no_posts_message      = self::get_option( 'no_posts_message', $atts );
		$offset                = self::get_option( 'offset', $atts );
		$order                 = self::get_option( 'order', $atts );
		$orderby               = self::get_option( 'orderby', $atts );
		$post_parent           = $atts['post_parent']; // Validated later, after check for 'current'.
		$post_parent__in       = $atts['post_parent__in'];
		$post_parent__not_in   = $atts['post_parent__not_in'];
		$post_status           = $atts['post_status']; // Validated later as one of a few values.
		$post_type             = self::get_option( 'post_type', $atts );
		$posts_per_page        = self::get_option( 'posts_per_page', $atts );
		$s                     = self::get_option( 's', $atts );
		$tag                   = self::get_option( 'tag', $atts );
		$tax_operator          = $atts['tax_operator']; // Validated later as one of a few values.
		$tax_include_children  = self::get_option( 'tax_include_children', $atts );
		$tax_term              = self::get_option( 'tax_term', $atts );
		$taxonomy              = self::get_option( 'taxonomy', $atts );
		$time                  = self::get_option( 'time', $atts );
		$shortcode_title       = self::get_option( 'title', $atts );
		$wrapper               = self::get_option( 'wrapper', $atts );
		$wrapper_class         = self::get_option( 'wrapper_class', $atts );

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
			$args['post_type'] = to_array( $post_type );
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
			$dates = sanitize_date_time( $date );
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
			$times = sanitize_date_time( $time, 'time' );
			if ( ! empty( $times ) ) {
				foreach ( $times as $arg => $segment ) {
					$initial_date_query[ $arg ] = $segment;
				}
			}

			// Date query 'before' argument.
			$before = sanitize_date_time( $date_query_before, 'date', true );
			if ( ! empty( $before ) ) {
				$initial_date_query['before'] = $before;
			}

			// Date query 'after' argument.
			$after = sanitize_date_time( $date_query_after, 'date', true );
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
		if ( self::get_option( 'ignore_sticky_posts', $atts ) ) {
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
			$posts_in         = array_map( 'intval', to_array( $id ) );
			$args['post__in'] = $posts_in;
		}

		// If Exclude.
		$post__not_in = array();
		if ( ! empty( $exclude ) ) {
			$post__not_in = array_map( 'intval', to_array( $exclude ) );
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
		$post_status = to_array( $post_status );
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
				//global $post;
				$terms    = wp_get_post_terms( get_the_ID(), $taxonomy );
				$tax_term = array();
				foreach ( $terms as $term ) {
					$tax_term[] = $term->slug;
				}
			} else {
				// Term string to array.
				$tax_term = to_array( $tax_term );
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
				$terms                = to_array( sanitize_text_field( $original_atts[ 'tax_' . $count . '_term' ] ) );
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
			$args['post_parent__in'] = array_map( 'intval', to_array( $atts['post_parent__in'] ) );
		}
		if ( false !== $post_parent__not_in ) {
			$args['post_parent__not_in'] = array_map( 'intval', to_array( $atts['post_parent__in'] ) );
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

			/**
			 * Filter the HTML markup to display image for the current post.
			 *
			 * @since 1.0
			 *
			 * @param string $image         HTML markup to display post image.
			 * @param string $image_size    The image size to display.
			 * @param bool   $include_link  Whether or not to display the image as the post permalink.
			 * @param array  $original_atts Original attributes passed to the shortcode.
			 */
			$image = apply_filters( 'Easy_Plugins/Display_Posts/Post/Image', $image, $image_size, $include_link, $original_atts );

			if ( self::get_option( 'include_date', $atts ) ) {
				$date = 'relative' === $date_format ? relative_date( get_the_date( 'U' ) ) : get_the_date( $date_format );
			} elseif ( self::get_option( 'include_date_modified', $atts ) ) {
				$date = 'relative' === $date_format ? relative_date( get_the_modified_time( 'U' ) ) : get_the_modified_date( $date_format );
			}
			if ( ! empty( $date ) ) {
				$date = ' <span class="date">' . $date . '</span>';
			}

			if ( self::get_option( 'include_author', $atts ) ) {

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

			if ( self::get_option( 'include_excerpt', $atts ) ) {

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
					if ( self::get_option( 'include_excerpt_dash', $atts ) ) {
						$excerpt = ' <span class="excerpt-dash">-</span>' . $excerpt;
					}
				}
			}

			if ( self::get_option( 'include_content', $atts ) ) {
				add_filter( 'shortcode_atts_display-posts', array( __CLASS__, 'ezp_display_posts_off' ), 10, 3 );
				/** This filter is documented in wp-includes/post-template.php */
				$content = '<div class="' . implode( ' ', self::get_option( 'content_class', $atts ) ) . '">' . apply_filters( 'the_content', get_the_content() ) . '</div>';
				remove_filter( 'shortcode_atts_display-posts', array( __CLASS__, 'ezp_display_posts_off' ), 10 );
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
					$category_display_text = ' <span class="category-display"><span class="category-display-label">' . self::get_option( 'category_label', $atts ) . '</span> ' . implode( ', ', $term_output ) . '</span>';
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
	 * Turn off display posts shortcode.
	 *
	 * If display full post content, any uses of [display-posts] are disabled.
	 *
	 * @param array $out   Returned shortcode values.
	 * @param array $pairs List of supported attributes and their defaults.
	 * @param array $atts  Original shortcode attributes.
	 * @return array
	 */
	public static function ezp_display_posts_off( $out, $pairs, $atts ) {

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
}
