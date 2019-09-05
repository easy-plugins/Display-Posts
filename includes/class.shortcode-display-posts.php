<?php

namespace Easy_Plugins\Display_Posts\Shortcode;

use Easy_Plugins\Display_Posts\Cache;
use Easy_Plugins\Display_Posts\Query;
use WP_Post;
use WP_Query;
use function Easy_Plugins\Display_Posts\Functions\{to_boolean};
use function Easy_Plugins\Display_Posts\Formatting\{relative_date};

class Display_Posts {

	/**
	 * @since 1.0
	 * @var array
	 */
	private $atts = array();

	/**
	 * @since 1.0
	 * @var array
	 */
	private $untrusted = array();

	/**
	 * Display_Posts constructor.
	 *
	 * @since 1.0
	 *
	 * @param array  $untrusted   User supplied attributes.
	 * @param string $content     The content enclosed within the shortcode.
	 * @param string $tag         The shortcode tag.
	 */
	protected function __construct( array $untrusted, string $content, string $tag ) {

		$this->untrusted = $untrusted;

		// Pull in shortcode attributes and set defaults.
		$this->atts = shortcode_atts( self::defaults(), $untrusted, $tag );
	}

	/**
	 * Callback for the display-posts shortcode.
	 *
	 * To customize, use the following filters: https://displayposts.com/docs/filters/
	 *
	 * @param array  $untrusted User supplied attributes.
	 * @param string $content   The content enclosed within the shortcode.
	 * @param string $tag       The shortcode tag.
	 *
	 * @return string
	 */
	public static function run( $untrusted, string $content, string $tag ) : string {

		// If no shortcode options are set, the $untrusted will be an empty string.
		if ( ! is_array( $untrusted ) ) {

			$untrusted = array();
		}

		/**
		 * Short circuit filter.
		 *
		 * Use this filter to return from this function immediately, with the return of the filter callback.
		 *
		 * @since 1.0
		 *
		 * @param bool  $short_circuit False to allow this function to continue, anything else to return that value.
		 * @param array $untrusted     Shortcode attributes.
		 */
		$render = apply_filters_deprecated( 'pre_display_posts_shortcode_output', array( FALSE ), '1.0', 'Easy_Plugins/Display_Posts/Render' );
		$render = apply_filters( 'Easy_Plugins/Display_Posts/Render', $render, $untrusted );

		if ( FALSE !== $render ) {

			return '';
		}

		$shortcode = new static( $untrusted, $content, $tag );

		return $shortcode->render();
	}

	/**
	 * @since 1.0
	 *
	 * @return array
	 */
	public function defaults() {

		return array(
			'category_display'      => '',
			'category_label'        => esc_html__( 'Posted in: ', 'easy-plugins-display-posts' ),
			'content_class'         => 'content',
			'date_format'           => '(n/j/Y)',
			'display_posts_off'     => FALSE,
			'excerpt_length'        => FALSE,
			'excerpt_more'          => FALSE,
			'excerpt_more_link'     => FALSE,
			'image_size'            => FALSE,
			'include_author'        => FALSE,
			'include_content'       => FALSE,
			'include_date'          => FALSE,
			'include_date_modified' => FALSE,
			'include_excerpt'       => FALSE,
			'include_excerpt_dash'  => TRUE,
			'include_link'          => TRUE,
			'include_title'         => TRUE,
			'no_posts_message'      => '',
			'title'                 => '',
			'wrapper'               => 'ul',
			'wrapper_class'         => 'display-posts-listing',
			'wrapper_id'            => FALSE,
		);
	}

	/**
	 * @since 1.0
	 *
	 * @param string $key
	 * @param null   $default
	 *
	 * @return array|bool|int|string|null
	 */
	private function get_option( string $key, $default = NULL ) {

		if ( ! in_array( $key, self::defaults() ) ) {

			return $default;
		}

		switch ( $key ) {

			case 'category_label':
			case 'date_format':
			case 'excerpt_more':
			case 'no_posts_message':
			case 'title':
			case 'wrapper':

				$value = sanitize_text_field( $this->atts[ $key ] );
				break;

			case 'display_posts_off':
			case 'excerpt_more_link':
			case 'include_author':
			case 'include_content':
			case 'include_date':
			case 'include_date_modified':
			case 'include_excerpt':
			case 'include_excerpt_dash':
			case 'include_link':
			case 'include_title':

				$value = to_boolean( $this->atts[ $key ] );
				break;

			case 'excerpt_length':

				$value = absint( $this->atts[ $key ] );
				break;

			case 'image_size':

			$value = sanitize_key( $this->atts[ $key ] );
				break;

			case 'content_class':
			case 'wrapper_class':

				$value = array_map( 'sanitize_html_class', explode( ' ', $this->atts[ $key ] ) );
				break;

			default:

				$value = $default;
		}

		return $value;
	}

	/**
	 * Callback for the display-posts shortcode.
	 *
	 * Render from the transient cache or render new instance and save to the transient cache.
	 *
	 * @since 1.0
	 *
	 * @param array  $untrusted   User supplied attributes.
	 * @param string $content The content enclosed within the shortcode.
	 * @param string $tag     The shortcode tag.
	 *
	 * @return string
	 */
	public function cache( array $untrusted, string $content, string $tag ) : string {

		$key = hash( 'crc32b', json_encode( $untrusted ) );

		$fragment = Cache::get( $key, 'transient', 'ezpdp' );
		$fragment = FALSE;

		if ( FALSE === $fragment ) {

			ob_start();

			echo self::run( $untrusted, $content, $tag );

			$fragment = ob_get_clean();

			Cache::set( $key, $fragment, WEEK_IN_SECONDS, 'transient', 'ezpdp' );
		}

		return $fragment;
	}

	/**
	 * Callback for various actions to clear all shortcode instances from the cache.
	 *
	 * @since 1.0
	 */
	public static function clear_cache() {

		Cache::clear( TRUE, 'transient', 'ezpdp' );
	}

	/**
	 * Render the display-posts shortcode.
	 *
	 * To customize, use the following filters: https://displayposts.com/docs/filters/
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function render() : string {

		// End early if shortcode should be turned off.
		if ( $this->get_option( 'display_posts_off' ) ) {
			return '';
		}

		$category_display      = 'true' === $this->atts['category_display'] ? 'category' : sanitize_text_field( $this->atts['category_display'] );
		$date_format           = $this->get_option( 'date_format' );
		$excerpt_length        = $this->get_option( 'excerpt_length' );
		$excerpt_more          = $this->get_option( 'excerpt_more' );
		$excerpt_more_link     = $this->get_option( 'excerpt_more_link' );
		$image_size            = $this->get_option( 'image_size' );
		$include_title         = $this->get_option( 'include_title' );
		$include_link          = $this->get_option( 'include_link' );
		$no_posts_message      = $this->get_option( 'no_posts_message' );
		$shortcode_title       = $this->get_option( 'title' );
		$wrapper               = $this->get_option( 'wrapper' );
		$wrapper_class         = $this->get_option( 'wrapper_class' );

		if ( ! empty( $wrapper_class ) ) {
			$wrapper_class = ' class="' . implode( ' ', $wrapper_class ) . '"';
		}
		$wrapper_id = sanitize_html_class( $this->atts['wrapper_id'] );
		if ( ! empty( $wrapper_id ) ) {
			$wrapper_id = ' id="' . $wrapper_id . '"';
		}

		// Set up html elements used to wrap the posts.
		// Default is ul/li, but can also be ol/li and div/div.
		$wrapper_options = array( 'ul', 'ol', 'div' );
		if ( ! in_array( $wrapper, $wrapper_options, true ) ) {
			$wrapper = 'ul';
		}
		$inner_wrapper = 'div' === $wrapper ? 'div' : 'li';

		$dps_listing = Query::run( $this->untrusted );

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
			 * @param array  $untrusted     Original attributes passed to the shortcode.
			 */
			$image = apply_filters( 'Easy_Plugins/Display_Posts/Post/Image', $image, $image_size, $include_link, $this->untrusted );

			if ( $this->get_option( 'include_date' ) ) {
				$date = 'relative' === $date_format ? relative_date( get_the_date( 'U' ) ) : get_the_date( $date_format );
			} elseif ( $this->get_option( 'include_date_modified' ) ) {
				$date = 'relative' === $date_format ? relative_date( get_the_modified_time( 'U' ) ) : get_the_modified_date( $date_format );
			}
			if ( ! empty( $date ) ) {
				$date = ' <span class="date">' . $date . '</span>';
			}

			if ( $this->get_option( 'include_author' ) ) {

				$author = ' <span class="author">by ' . get_the_author() . '</span>';

				/**
				 * Filter the HTML markup to display author information for the current post.
				 *
				 * @since 1.0
				 *
				 * @param string $author_output HTML markup to display author information.
				 */
				$author = apply_filters_deprecated( 'display_posts_shortcode_author', array( $author, $this->untrusted ), '1.0', 'Easy_Plugins/Display_Posts/Post/Author' );
				$author = apply_filters( 'Easy_Plugins/Display_Posts/Post/Author', $author, $this->untrusted );
			}

			if ( $this->get_option( 'include_excerpt' ) ) {

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
					if ( $this->get_option( 'include_excerpt_dash' ) ) {
						$excerpt = ' <span class="excerpt-dash">-</span>' . $excerpt;
					}
				}
			}

			if ( $this->get_option( 'include_content' ) ) {
				add_filter( 'shortcode_atts_display-posts', array( __CLASS__, 'ezp_display_posts_off' ), 10, 3 );
				/** This filter is documented in wp-includes/post-template.php */
				$content = '<div class="' . implode( ' ', $this->get_option( 'content_class' ) ) . '">' . apply_filters( 'the_content', get_the_content() ) . '</div>';
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
					$category_display_text = ' <span class="category-display"><span class="category-display-label">' . $this->get_option( 'category_label' ) . '</span> ' . implode( ', ', $term_output ) . '</span>';
				}

				/**
				 * Filter the list of categories attached to the current post.
				 *
				 * @since 1.0
				 *
				 * @param string   $category_display Current Category Display text
				 */
				$category_display_text = apply_filters_deprecated( 'display_posts_shortcode_category_display', array( $category_display_text, $terms, $category_display, $this->untrusted ), '1.0', 'Easy_Plugins/Display_Posts/Post/Categories' );
				$category_display_text = apply_filters( 'Easy_Plugins/Display_Posts/Post/Categories', $category_display_text, $terms, $category_display, $this->untrusted );
			}

			$class = array( 'listing-item' );

			/**
			 * Filter the post classes for the inner wrapper element of the current post.
			 *
			 * @since 1.0
			 *
			 * @param array    $class       Post classes.
			 * @param WP_Post  $post        Post object.
			 * @param WP_Query $dps_listing WP_Query object for the posts listing.
			 * @param array    $untrusted   Original attributes passed to the shortcode.
			 */
			$class = apply_filters_deprecated( 'display_posts_shortcode_post_class', array( $class, $post, $dps_listing, $this->untrusted ), '1.0', 'Easy_Plugins/Display_Posts/Post/Class' );
			$class = apply_filters( 'Easy_Plugins/Display_Posts/Post/Class', $class, $post, $dps_listing, $this->untrusted );

			$class  = array_map( 'sanitize_html_class', $class );
			$output = '<' . $inner_wrapper . ' class="' . implode( ' ', $class ) . '">' . $image . $title . $date . $author . $category_display_text . $excerpt . $content . '</' . $inner_wrapper . '>';

			/**
			 * Filter the HTML markup for output via the shortcode.
			 *
			 * @since 1.0
			 *
			 * @param string $output        The shortcode's HTML output.
			 * @param array  $untrusted     Original attributes passed to the shortcode.
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
			$output = apply_filters_deprecated( 'display_posts_shortcode_output', array( $output, $this->untrusted, $image, $title, $date, $excerpt, $inner_wrapper, $content, $class, $author, $category_display_text ), '1.0', 'Easy_Plugins/Display_Posts/Post/HTML' );
			$inner .= apply_filters( 'Easy_Plugins/Display_Posts/Post/HTML', $output, $this->untrusted, $image, $title, $date, $excerpt, $inner_wrapper, $content, $class, $author, $category_display_text );

		endwhile;
		wp_reset_postdata();

		/**
		 * Filter the shortcode output's opening outer wrapper element.
		 *
		 * @since 1.0
		 *
		 * @param string $wrapper_open HTML markup for the opening outer wrapper element.
		 * @param array  $untrusted    Original attributes passed to the shortcode.
		 * @param object $dps_listing  WP Query object
		 */
		$open = apply_filters_deprecated( 'display_posts_shortcode_wrapper_open', array( '<' . $wrapper . $wrapper_class . $wrapper_id . '>', $this->untrusted, $dps_listing ), '1.0', 'Easy_Plugins/Display_Posts/HTML/Wrap_Open' );
		$open = apply_filters( 'Easy_Plugins/Display_Posts/HTML/Wrap_Open', $open, $this->untrusted, $dps_listing );

		/**
		 * Filter the shortcode output's closing outer wrapper element.
		 *
		 * @since 1.0
		 *
		 * @param string $wrapper_close HTML markup for the closing outer wrapper element.
		 * @param array  $untrusted     Original attributes passed to the shortcode.
		 * @param object $dps_listing   WP Query object
		 */
		$close = apply_filters_deprecated( 'display_posts_shortcode_wrapper_close', array( '</' . $wrapper . '>', $this->untrusted, $dps_listing ), '1.0', 'Easy_Plugins/Display_Posts/HTML/Wrap_Close' );
		$close = apply_filters( 'Easy_Plugins/Display_Posts/HTML/Wrap_Close', $close, $this->untrusted, $dps_listing );

		$return = '';

		if ( $shortcode_title ) {

			/**
			 * Filter the shortcode output title tag element.
			 *
			 * @since 1.0
			 *
			 * @param string $tag       Type of element to use for the output title tag. Default 'h2'.
			 * @param array  $untrusted Original attributes passed to the shortcode.
			 */
			$title_tag = apply_filters_deprecated( 'display_posts_shortcode_title_tag', array( 'h2', $this->untrusted ), '1.0', 'Easy_Plugins/Display_Posts/Posts/HTML/Title_Tag' );
			$title_tag = apply_filters( 'Easy_Plugins/Display_Posts/Posts/HTML/Title_Tag', $title_tag, $this->untrusted );

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
	 *
	 * @return array
	 */
	public static function ezp_display_posts_off( array $out, array $pairs, array $atts ) : array {

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
