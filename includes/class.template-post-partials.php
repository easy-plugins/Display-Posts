<?php

namespace Easy_Plugins\Display_Posts\Template\Post;

use function Easy_Plugins\Display_Posts\Formatting\relative_date;
use function Easy_Plugins\Display_Posts\Functions\to_boolean;

class Partials {

	/**
	 * @since 1.0
	 * @var array
	 */
	private $atts = array();

	public function __construct( array $atts ) {

		$this->atts = $atts;
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

		if ( ! array_key_exists( $key, $this->atts ) ) {

			return $default;
		}

		switch ( $key ) {

			case 'content_class':

				$value = array_map( 'sanitize_html_class', explode( ' ', $this->atts[ $key ] ) );
				break;

			case 'category_label':
			case 'date_format':
			case 'excerpt_more':

				$value = sanitize_text_field( $this->atts[ $key ] );
				break;

			case 'excerpt_length':

				$value = absint( $this->atts[ $key ] );
				break;

			case 'image_size':

				$value = sanitize_key( $this->atts[ $key ] );
				break;

			case 'category_display':
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

			default:

				$value = $default;
		}

		return $value;
	}

	/**
	 * @since 1.0
	 *
	 * @return string
	 */
	public function the_title() : string {

		$include_title = $this->get_option( 'include_title', TRUE );
		$include_link  = $this->get_option( 'include_link', TRUE );
		$html          = '';

		if ( $include_title && $include_link ) {

			/** This filter is documented in wp-includes/link-template.php */
			$html = '<a class="title" href="' . apply_filters( 'the_permalink', get_permalink() ) . '">' . get_the_title() . '</a>';

		} elseif ( $include_title ) {

			$html = '<span class="title">' . get_the_title() . '</span>';
		}

		return $html;
	}

	/**
	 * @since 1.0
	 *
	 * @return string
	 */
	public function the_featured_image() : string {

		$image_size   = $this->get_option( 'image_size', '' );
		$include_link = $this->get_option( 'include_link', TRUE );
		$html        = '';

		if ( $image_size && has_post_thumbnail() && $include_link ) {

			$html = '<a class="image" href="' . get_permalink() . '">' . get_the_post_thumbnail( get_the_ID(), $image_size ) . '</a>';

		} elseif ( $image_size && has_post_thumbnail() ) {

			$html = '<span class="image">' . get_the_post_thumbnail( get_the_ID(), $image_size ) . '</span>';
		}

		/**
		 * Filter the HTML markup to display image for the current post.
		 *
		 * @since 1.0
		 *
		 * @param string $html         HTML markup to display post image.
		 * @param string $image_size   The image size to display.
		 * @param bool   $include_link Whether or not to display the image as the post permalink.
		 * @param array  $atts         Original attributes passed to the shortcode.
		 */
		return apply_filters( 'Easy_Plugins/Display_Posts/Post/Image', $html, $image_size, $include_link, $this->atts );
	}

	/**
	 * @since 1.0
	 *
	 * @return string
	 */
	public function the_date() : string {

		$date_format = $this->get_option( 'date_format', '(n/j/Y)' );
		$html        = '';

		if ( $this->get_option( 'include_date', FALSE ) ) {

			$html = 'relative' === $date_format ? relative_date( get_the_date( 'U' ) ) : get_the_date( $date_format );

		} elseif ( $this->get_option( 'include_date_modified', FALSE ) ) {

			$html = 'relative' === $date_format ? relative_date( get_the_modified_time( 'U' ) ) : get_the_modified_date( $date_format );
		}

		if ( ! empty( $html ) ) {

			$html = '<span class="date">' . $html . '</span>';
		}

		return $html;
	}

	/**
	 * @since 1.0
	 *
	 * @return string
	 */
	public function the_author() : string {

		$html = '';

		if ( $this->get_option( 'include_author', FALSE ) ) {

			$html = '<span class="author">' . sprintf( _x( 'by %s', 'post author', 'easy-plugins-display-posts' ), get_the_author() ) . '</span>';
		}

		/**
		 * Filter the HTML markup to display author information for the current post.
		 *
		 * @since 1.0
		 *
		 * @param string $author_output HTML markup to display author information.
		 */
		$html = apply_filters_deprecated( 'display_posts_shortcode_author', array( $html, $this->atts ), '1.0', 'Easy_Plugins/Display_Posts/Post/Author' );
		return apply_filters( 'Easy_Plugins/Display_Posts/Post/Author', $html, $this->atts );
	}

	/**
	 * @since 1.0
	 *
	 * @return string
	 */
	public function the_excerpt() : string {

		global $post;

		$excerpt_length    = $this->get_option( 'excerpt_length', FALSE );
		$excerpt_more      = $this->get_option( 'excerpt_more', FALSE );
		$excerpt_more_link = $this->get_option( 'excerpt_more_link', FALSE );
		$html              = '';

		if ( $this->get_option( 'include_excerpt', FALSE ) ) {

			// Custom build excerpt based on shortcode parameters.
			if ( $excerpt_length || $excerpt_more || $excerpt_more_link ) {

				$length = $excerpt_length ? $excerpt_length : apply_filters( 'excerpt_length', 55 );
				$more   = $excerpt_more ? $excerpt_more : apply_filters( 'excerpt_more', '' );
				$more   = $excerpt_more_link ? ' <a class="excerpt-more" href="' . get_permalink() . '">' . $more . '</a>' : ' <span class="excerpt-more">' . $more . '</span>';

				$full_manual_excerpt = apply_filters_deprecated( 'display_posts_shortcode_full_manual_excerpt', array( FALSE ), '1.0', 'Easy_Plugins/Display_Posts/Post/Manual_Excerpt' );
				$full_manual_excerpt = apply_filters( 'Easy_Plugins/Display_Posts/Post/Manual_Excerpt', $full_manual_excerpt );

				if ( has_excerpt() && $full_manual_excerpt ) {

					$html = $post->post_excerpt . $more;

				} elseif ( has_excerpt() ) {

					$html = wp_trim_words( strip_shortcodes( $post->post_excerpt ), $length ) . $more;

				} else {

					$excerpt = wp_trim_words( strip_shortcodes( $post->post_content ), $length );

					if ( 0 < strlen( $excerpt ) ) {

						$html = $excerpt . $more;
					}
				}

			// Use default, can customize with WP filters.
			} else {

				$html = get_the_excerpt();
			}

			if ( ! empty( $html ) ) {

				$html = '<span class="excerpt">' . $html . '</span>';

				if ( $this->get_option( 'include_excerpt_dash', TRUE ) ) {

					$html = '<span class="excerpt-dash">-</span> ' . $html;
				}
			}
		}

		return $html;
	}

	/**
	 * @since 1.0
	 *
	 * @return string
	 */
	public function the_content() : string {

		$html = '';

		if ( $this->get_option( 'include_content', FALSE ) ) {

			add_filter( 'shortcode_atts_display-posts', array( 'Easy_Plugins\Display_Posts\Shortcode\Display_Posts', 'ezp_display_posts_off' ), 10, 3 );

			/** This filter is documented in wp-includes/post-template.php */
			$html = '<div class="' . implode( ' ', $this->get_option( 'content_class', 'content' ) ) . '">' . apply_filters( 'the_content', get_the_content() ) . '</div>';

			remove_filter( 'shortcode_atts_display-posts', array( 'Easy_Plugins\Display_Posts\Shortcode\Display_Posts', 'ezp_display_posts_off' ), 10 );
		}

		return $html;
	}

	/**
	 * @since 1.0
	 *
	 * @return string
	 */
	public function the_terms() : string {

		$display  = $this->get_option( 'category_display', FALSE );
		$taxonomy = 'category';
		$html     = '';

		if ( isset( $this->atts['category_display'] ) &&
		     ! empty( $this->atts['category_display'] ) &&
		     is_string( $this->atts['category_display'] ) &&
		     ! in_array( strtolower( $this->atts['category_display'] ), array( '0', '1', 'true', 'false' ), TRUE )
		) {

			$taxonomy = sanitize_text_field( $this->atts['category_display'] );
		}

		if ( $display && is_object_in_taxonomy( get_post_type(), $taxonomy ) ) {

			$terms       = get_the_terms( get_the_ID(), $taxonomy );
			$term_output = array();

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {

				foreach ( $terms as $term ) {

					$term_output[] = '<a href="' . get_term_link( $term, $taxonomy ) . '">' . $term->name . '</a>';
				}

				$html = '<span class="category-display"><span class="category-display-label">' . $this->get_option( 'category_label', esc_html__( 'Posted in: ', 'easy-plugins-display-posts' ) ) . '</span> ' . implode( ', ', $term_output ) . '</span>';
			}

			/**
			 * Filter the list of categories attached to the current post.
			 *
			 * @since 1.0
			 *
			 * @param string   $category_display Current Category Display text
			 */
			$html = apply_filters_deprecated( 'display_posts_shortcode_category_display', array( $html, $terms, $taxonomy, $this->atts ), '1.0', 'Easy_Plugins/Display_Posts/Post/Categories' );
			$html = apply_filters( 'Easy_Plugins/Display_Posts/Post/Categories', $html, $terms, $taxonomy, $this->atts );
		}

		return  $html;
	}
}
