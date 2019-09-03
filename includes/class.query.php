<?php

namespace Easy_Plugins\Display_Posts;

use WP_Query;
use function Easy_Plugins\Display_Posts\Functions\sanitize_date_time;
use function Easy_Plugins\Display_Posts\Functions\{to_array, to_boolean};

/**
 * Helper class for the shortcode render callback.
 *
 * This is to parse the shortcode options and setup the query args.
 *
 * @since 1.0
 *
 * @package Easy_Plugins\Display_Posts
 */
class Query {

	/**
	 * @since 1.0
	 * @var array
	 */
	private $args = array(
		'offset'         => 0,
		'order'          => 'DESC',
		'orderby'        => 'date',
		'perm'           => 'readable',
		'post_status'    => 'publish',
		'post_type'      => 'post',
		'posts_per_page' => 10,
	);

	/**
	 * @since 1.0
	 * @var array
	 */
	private $atts = array();

	/**
	 * @since 1.0
	 * @var WP_Query
	 */
	private $query;

	/**
	 * Query constructor.
	 *
	 * @since 1.0
	 *
	 * @param array $args
	 */
	public function __construct( $args ) {

		$this->setup_query_options( $args );
		$this->parse_query_args( $args );

		$this->query = new WP_Query( $this->args );
	}

	/**
	 * @since 1.0
	 *
	 * @return WP_Query
	 */
	public function get_query_results() {

		return $this->query;
	}

	/**
	 * @since 1.0
	 *
	 * @param array $args
	 *
	 * @return WP_Query
	 */
	public static function run( $args ) {

		$query = new static( $args );

		return $query->get_query_results();
	}

	/**
	 * Retrieve the sanitized shortcode option value by key.
	 *
	 * @since 1.0
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return array|bool|int|string
	 */
	private function get_option( $key, $default = NULL ) {

		if ( ! array_key_exists( $key, $this->atts ) ) {

			return $default;
		}

		switch ( $key ) {

			case 'tax_term':
			case 'taxonomy':

				$value = sanitize_key( $this->atts[ $key ] );
				break;

			case 'date':
			case 'date_column':
			case 'date_compare':
			case 'date_query_before':
			case 'date_query_after':
			case 'date_query_column':
			case 'date_query_compare':
			case 'tax_operator':
			case 'time':

				$value = sanitize_text_field( $this->atts[ $key ] );
				break;

			case 'has_password':

				$value = ! is_null( $this->atts[ $key ] ) ? to_boolean( $this->atts[ $key ] ) : NULL;
				break;

			case 'exclude_current':
			case 'ignore_sticky_posts':
			case 'post_parent':
			case 'tax_include_children':

				$value = to_boolean( $this->atts[ $key ] );
				break;

			default:
				$value = $default;
		}

		return $value;
	}

	/**
	 * Add a query arg.
	 *
	 * @since 1.0
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	public function add_query_arg( $key, $value ) {

		switch ( $key ) {

			case 'date_query':

				$this->args['date_query'] = $value;
				break;

			case 'exclude':

				$this->args['post__not_in'] = $value; // phpcs:ignore WordPressVIPMinimum.VIP.WPQueryParams.post__not_in
				break;

			case 'id':

				$args['post__in'] = array_map( 'intval', to_array( $value ) );
				break;

			case 'has_password':
			case 'ignore_sticky_posts':

				$this->args[ $key ] = to_boolean( $value );
				break;

			case 'order':
			case 'orderby':

				$this->args[ $key ] = sanitize_key( $value );
				break;

			case 'post_parent__in':
			case 'post_parent__not_in':

				$this->args[ $key ] = array_map( 'absint', to_array( $value ) );
				break;

			case 'post_status':

				$this->args['post_status'] = $value;
				break;

			case 'post_type':

				$this->args['post_type'] = to_array( sanitize_text_field( $value ) );
				break;

			case 'author_id':
			case 'category_id':
			case 'offset':
			case 'post_parent':
			case 'posts_per_page':

				$this->args[ $key ] = absint( $value );
				break;

			case 'author':
			case 'category':
			case 'meta_key':
			case 'meta_value':
			case 's':
			case 'tag':

				$this->args[ $key ] = sanitize_text_field( $value );
				break;

			case 'tax_query':

				$this->args['tax_query'] = $value;
				break;
		}
	}

	/**
	 * Store a copy of the supplied shortcode option in a class variable.
	 *
	 * @see Query::get_option()
	 *
	 * @since 1.0
	 *
	 * @param array $atts
	 */
	private function setup_query_options( $atts ) {

		$this->atts = $atts;
	}

	/**
	 * Parse the supplied shortcode options and add them to the posts query args.
	 *
	 * @since 1.0
	 *
	 * @param array $args
	 */
	private function parse_query_args( $args ) {

		foreach ( $args as $key => $value ) {

			$this->add_query_arg( $key, $value );
		}

		// Ignore sticky posts.
		if ( TRUE === $this->get_option( 'ignore_sticky_posts', FALSE ) ) {

			$this->add_query_arg( 'ignore_sticky_posts', TRUE );
		}

		// Password protected.
		if ( NULL !== $has_password = $this->get_option( 'has_password', NULL ) ) {

			$this->add_query_arg( 'has_password', $has_password );
		}

		// Post Author.
		$this->parse_post_author( $args );

		// If Post IDs.
		$this->parse_post_id( $args );

		// If Exclude.
		$this->parse_exclude_posts( $args );

		// Post Status.
		$this->parse_post_status( $args );

		// If taxonomy attributes, create a taxonomy query.
		$this->setup_taxonomy_query( $args );

		// Date query.
		$this->setup_date_query();

		// If post parent attribute, set up parent.
		$this->parse_post_parent( $args );

		/**
		 * Filter the arguments passed to WP_Query.
		 *
		 * @since 1.0
		 *
		 * @param array $args          Parsed arguments to pass to WP_Query.
		 * @param array $original_atts Original attributes passed to the shortcode.
		 */
		$this->args = apply_filters_deprecated( 'display_posts_shortcode_args', array( $this->args, $args ), '1.0', 'Easy_Plugins/Display_Posts/Query/Args' );
		$this->args = apply_filters( 'Easy_Plugins/Display_Posts/Query/Args', $this->args, $args );
	}

	/**
	 * Parse the shortcode `author` attribute.
	 *
	 * @see Query::parse_query_args()
	 *
	 * @since 1.0
	 *
	 * @param array $args
	 */
	private function parse_post_author( $args ) {

		if ( ! array_key_exists( 'author', $args ) ) {

			return;
		}

		if ( 'current' === $args['author'] && is_user_logged_in() ) {

			$author = wp_get_current_user()->user_login;

		} elseif ( 'current' === $args['author'] ) {

			$author = 'dps_no_results';

		} else {

			$author = $args['author'];
		}

		$this->add_query_arg( 'author', $author );
	}

	/**
	 * Parse the shortcode `id` attribute.
	 *
	 * @see Query::parse_query_args()
	 *
	 * @since 1.0
	 *
	 * @param array $args
	 */
	private function parse_post_id( $args ) {

		if ( ! array_key_exists( 'id', $args ) ) {

			return;
		}

		$this->add_query_arg( 'id', $args['id'] );
	}

	/**
	 * Parse the shortcode `exclude` attribute.
	 *
	 * @see Query::parse_query_args()
	 *
	 * @since 1.0
	 *
	 * @param array $args
	 */
	private function parse_exclude_posts( $args ) {

		if ( ! array_key_exists( 'exclude', $args ) ) {

			return;
		}

		$post__not_in = array();

		if ( ! empty( $args['exclude'] ) ) {

			$post__not_in = array_map( 'intval', to_array( $args['exclude'] ) );
		}

		if ( is_singular() && $this->get_option( 'exclude_current', FALSE ) ) {

			$post__not_in[] = get_the_ID();
		}

		if ( ! empty( $post__not_in ) ) {

			$this->add_query_arg( 'exclude', $post__not_in );
		}
	}

	/**
	 * Parse the shortcode `post_status` attribute.
	 *
	 * @see Query::parse_query_args()
	 *
	 * @since 1.0
	 *
	 * @param array $args
	 */
	private function parse_post_status( $args ) {

		if ( ! array_key_exists( 'post_status', $args ) ) {

			return;
		}

		$status = to_array( $args['post_status'] );

		if ( empty( $status ) ) {

			return;
		}

		$validated = array();
		$permitted = array(
			'publish',
			'pending',
			'draft',
			'auto-draft',
			'future',
			'private',
			'inherit',
			'trash',
			'any',
		);

		foreach ( $status as $unvalidated ) {

			if ( in_array( $unvalidated, $permitted, TRUE ) ) {

				$validated[] = $unvalidated;
			}
		}

		if ( ! empty( $validated ) ) {

			$this->add_query_arg( 'post_status', $validated );
		}
	}

	/**
	 * Parse the `post_parent` shortcode attribute.
	 *
	 * @see Query::parse_query_args()
	 *
	 * @since 1.0
	 *
	 * @param array $args
	 */
	private function parse_post_parent( $args ) {

		if ( ! array_key_exists( 'post_parent', $args ) ) {

			return;
		}

		if ( FALSE !== $this->get_option( 'post_parent', FALSE ) ) {

			$post_parent = 'current' === $args['post_parent'] ? get_the_ID() : $args['post_parent'];

			$this->add_query_arg( 'post_parent', $post_parent );
		}
	}

	/**
	 * Setup the taxonomy query args from the shortcode attributes.
	 *
	 * @see Query::parse_query_args()
	 *
	 * @since 1.0
	 *
	 * @param array $args
	 */
	private function setup_taxonomy_query( $args ) {

		if ( ! array_key_exists( 'taxonomy', $args ) &&
		     ! array_key_exists( 'tax_term', $args )) {

			return;
		}

		$taxonomy     = $this->get_option( 'taxonomy', 'IN' );
		$tax_term     = $this->get_option( 'tax_term', 'IN' );
		$tax_operator = $this->get_option( 'tax_operator', 'IN' );

		if ( ! empty( $taxonomy ) && ! empty( $tax_term ) ) {

			if ( 'current' === $tax_term ) {

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
			if ( ! in_array( $tax_operator, array( 'IN', 'NOT IN', 'AND' ), TRUE ) ) {

				$tax_operator = 'IN';
			}

			$tax_args = array(
				// phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy'         => $taxonomy,
					'field'            => 'slug',
					'terms'            => $tax_term,
					'operator'         => $tax_operator,
					'include_children' => $this->get_option( 'tax_include_children', TRUE ),
				),
			);

			// Check for multiple taxonomy queries.
			$count            = 2;
			$more_tax_queries = FALSE;

			while ( isset( $args["taxonomy_{$count}"] ) && ! empty( $args["taxonomy_{$count}"] ) &&
			        isset( $args["tax_{$count}_term"] ) && ! empty( $args["tax_{$count}_term"] ) ) {

				// Sanitize values.
				$more_tax_queries     = TRUE;
				$taxonomy             = sanitize_key( $args["taxonomy_{$count}"] );
				$terms                = to_array( sanitize_text_field( $args["tax_{$count}_term"] ) );
				$tax_operator         = isset( $args["tax_{$count}_operator"] ) ? $args["tax_{$count}_operator"] : 'IN';
				$tax_operator         = in_array( $tax_operator, array( 'IN', 'NOT IN', 'AND' ), TRUE ) ? $tax_operator : 'IN';
				$tax_include_children = isset( $args["tax_{$count}_include_children"] ) ? filter_var( $args["tax_{$count}_include_children"], FILTER_VALIDATE_BOOLEAN ) : TRUE;

				$tax_args[] = array(
					'taxonomy'         => $taxonomy,
					'field'            => 'slug',
					'terms'            => $terms,
					'operator'         => $tax_operator,
					'include_children' => $tax_include_children,
				);

				$count ++;
			}

			if ( $more_tax_queries ) {

				$tax_relation = 'AND';

				if ( isset( $args['tax_relation'] ) && in_array( $args['tax_relation'], array( 'AND', 'OR' ), TRUE ) ) {

					$tax_relation = $args['tax_relation'];
				}

				$tax_args['relation'] = $tax_relation;
			}

			$this->add_query_arg( 'tax_query', $tax_args );
		}
	}

	/**
	 * Setup the date query args from the shortcode attributes.
	 *
	 * @see Query::parse_query_args()
	 *
	 * @since 1.0
	 */
	private function setup_date_query() {

		$date               = $this->get_option( 'date', '' );
		$date_column        = $this->get_option( 'date_column', 'post_date' );
		$date_compare       = $this->get_option( 'date_compare', '=' );
		$date_query_before  = $this->get_option( 'date_query_before', '' );
		$date_query_after   = $this->get_option( 'date_query_after', '' );
		$date_query_column  = $this->get_option( 'date_query_column', '' );
		$date_query_compare = $this->get_option( 'date_query_compare', '' );
		$time               = $this->get_option( 'time', '' );

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
			$before = sanitize_date_time( $date_query_before, 'date', TRUE );

			if ( ! empty( $before ) ) {

				$initial_date_query['before'] = $before;
			}

			// Date query 'after' argument.
			$after = sanitize_date_time( $date_query_after, 'date', TRUE );

			if ( ! empty( $after ) ) {

				$initial_date_query['after'] = $after;
			}

			// Date query 'column' argument.
			if ( ! empty( $date_query_column ) && in_array( $date_query_column, $valid_date_columns, TRUE ) ) {

				$initial_date_query['column'] = $date_query_column;
			}

			// Date query 'compare' argument.
			if ( ! empty( $date_query_compare ) && in_array( $date_query_compare, $valid_compare_ops, TRUE ) ) {
				$initial_date_query['compare'] = $date_query_compare;
			}

			// Top-level date_query arguments. Only valid arguments will be added.

			// 'column' argument.
			if ( ! empty( $date_column ) && in_array( $date_column, $valid_date_columns, TRUE ) ) {

				$date_query_top_lvl['column'] = $date_column;
			}

			// 'compare' argument.
			if ( ! empty( $date_compare ) && in_array( $date_compare, $valid_compare_ops, TRUE ) ) {

				$date_query_top_lvl['compare'] = $date_compare;
			}

			// Bring in the initial date query.
			if ( ! empty( $initial_date_query ) ) {

				$date_query_top_lvl[] = $initial_date_query;
			}

			//$this->args['date_query'] = $date_query_top_lvl;
			$this->add_query_arg( 'date_query', $date_query_top_lvl );
		}
	}

}
