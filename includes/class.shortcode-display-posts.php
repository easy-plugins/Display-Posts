<?php

namespace Easy_Plugins\Display_Posts\Shortcode;

use Easy_Plugins\Display_Posts\Cache;
use Easy_Plugins\Display_Posts\Query;
use Easy_Plugins\Display_Posts\Template\Loader;
use Easy_Plugins\Display_Posts\Template\Post\Partials;
use WP_Post;
use WP_Query;
use function Easy_Plugins\Display_Posts\Template\Partials\no_posts_message;
use function Easy_Plugins\Display_Posts\Template\Partials\posts_list_heading;
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
		$this->atts = shortcode_atts( $this->defaults(), $untrusted, $tag );
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
			'display_posts_off' => FALSE,
			'no_posts_message'  => '',
			'title'             => '',
			'template'          => '',
			'wrapper'           => 'ul',
			'wrapper_class'     => 'display-posts-listing',
			'wrapper_id'        => '',
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

		if ( ! array_key_exists( $key, $this->atts ) ) {

			return $default;
		}

		switch ( $key ) {

			case 'no_posts_message':
			case 'template':
			case 'title':
			case 'wrapper':

				$value = sanitize_text_field( $this->atts[ $key ] );
				break;

			case 'display_posts_off':

				$value = to_boolean( $this->atts[ $key ] );
				break;

			case 'wrapper_class':

				$value = array_map( 'sanitize_html_class', explode( ' ', $this->atts[ $key ] ) );
				break;

			case 'wrapper_id':

				$value = sanitize_html_class( $this->atts[ $key ] );
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
		if ( $this->get_option( 'display_posts_off', FALSE ) ) {
			return '';
		}

		$dps_listing = Query::run( $this->untrusted );

		if ( ! $dps_listing->have_posts() ) {

			return no_posts_message( $this->get_option( 'no_posts_message', '' ) );
		}

		$default_template  = TRUE;
		$wrapper_outer_tag = $this->get_option( 'wrapper', 'ul' );
		$inner             = '';

		// Set up html elements used to wrap the posts.
		// Default is ul/li, but can also be ol/li and div/div.
		if ( ! in_array( $wrapper_outer_tag, array( 'ul', 'ol', 'div' ), true ) ) {
			$wrapper_outer_tag = 'ul';
		}

		$wrapper_inner_tag = 'div' === $wrapper_outer_tag ? 'div' : 'li';

		// Get the template specified by user.
		if ( ! empty( $template = $this->get_option( 'template' ) ) ) {

			$default_template = FALSE;
			$template_path    = Loader::get_template_path( $template );
		}

		// If no template was specified by user or user misspelled the template name, get the default template.
		if ( empty( $template_path ) ) {

			$default_template = TRUE;
			$template_path    = Loader::get_template_path( 'default' );
		}

		while ( $dps_listing->have_posts() ) :
			$dps_listing->the_post();
			global $post;

			$partial = new Partials( $this->untrusted );

			$title   = $partial->the_title();
			$image   = $partial->the_featured_image();
			$date    = $partial->the_date();
			$author  = $partial->the_author();
			$excerpt = $partial->the_excerpt();
			$content = $partial->the_content();
			$terms   = $partial->the_terms();

			$class = array( 'listing-item' );

			/**
			 * Filter the post classes for the inner wrapper element of the current post.
			 *
			 * @deprecated
			 * @since 1.0
			 *
			 * @param array    $class       Post classes.
			 * @param WP_Post  $post        Post object.
			 * @param WP_Query $dps_listing WP_Query object for the posts listing.
			 * @param array    $untrusted   Original attributes passed to the shortcode.
			 */
			$class = apply_filters_deprecated( 'display_posts_shortcode_post_class', array( $class, $post, $dps_listing, $this->untrusted ), '1.0', 'Easy_Plugins/Display_Posts/Post/Class' );

			/**
			 * @since 1.0
			 *
			 * @param array $class        Post classes.
			 * @param $this Display_Posts
			 */
			$class = apply_filters( 'Easy_Plugins/Display_Posts/Post/Class', $class, $this );
			$class = array_map( 'sanitize_html_class', $class );

			ob_start();

			/**
			 * @since 1.0
			 *
			 * @param Display_Posts $this
			 * @param Partials      $partial
			 */
			do_action( 'Easy_Plugins/Display_Posts/Post/Before', $this, $partial );

			/** @noinspection PhpIncludeInspection */
			include $template_path;

			/**
			 * @since 1.0
			 *
			 * @param Display_Posts $this
			 * @param Partials      $partial
			 */
			do_action( 'Easy_Plugins/Display_Posts/Post/After', $this, $partial );

			$output = ob_get_clean();

			// Only run the filter if the default template is being used.
			if ( TRUE === $default_template ) {

				/**
				 * Filter the HTML markup for output via the shortcode.
				 *
				 * @since 1.0
				 *
				 * @param string $output            The shortcode's HTML output.
				 * @param array  $untrusted         Original attributes passed to the shortcode.
				 * @param string $image             HTML markup for the post's featured image element.
				 * @param string $title             HTML markup for the post's title element.
				 * @param string $date              HTML markup for the post's date element.
				 * @param string $excerpt           HTML markup for the post's excerpt element.
				 * @param string $wrapper_inner_tag Type of container to use for the post's inner wrapper element.
				 * @param string $content           The post's content.
				 * @param string $class             Space-separated list of post classes to supply to the $wrapper_inner_tag element.
				 * @param string $author            HTML markup for the post's author.
				 * @param string $terms
				 */
				$output = apply_filters_deprecated( 'display_posts_shortcode_output', array( $output, $this->untrusted, $image . ' ', $title, ' ' . $date, ' ' .  $excerpt, $wrapper_inner_tag, $content, $class, ' ' . $author, ' ' . $terms ), '1.0', 'Easy_Plugins/Display_Posts/Post/HTML' );
				$inner .= apply_filters( 'Easy_Plugins/Display_Posts/Post/HTML', $output, $this, $partial, $wrapper_inner_tag, $class );

			} else {

				$inner .= $output;
			}

		endwhile;
		wp_reset_postdata();

		$wrapper_class = $this->get_option( 'wrapper_class', 'display-posts-listing' );
		$wrapper_id    = $this->get_option( 'wrapper_id', '' );

		if ( ! empty( $wrapper_class ) ) {
			$wrapper_class = ' class="' . implode( ' ', $wrapper_class ) . '"';
		}

		if ( ! empty( $wrapper_id ) ) {
			$wrapper_id = ' id="' . $wrapper_id . '"';
		}

		/**
		 * Filter the shortcode output's opening outer wrapper element.
		 *
		 * @deprecated
		 * @since 1.0
		 *
		 * @param string $wrapper_open HTML markup for the opening outer wrapper element.
		 * @param array  $untrusted    Original attributes passed to the shortcode.
		 * @param object $dps_listing  WP Query object
		 */
		$open = apply_filters_deprecated( 'display_posts_shortcode_wrapper_open', array( '<' . $wrapper_outer_tag . $wrapper_class . $wrapper_id . '>', $this->untrusted, $dps_listing ), '1.0', 'Easy_Plugins/Display_Posts/HTML/Wrap_Open' );

		/**
		 * Filter the shortcode output's opening outer wrapper element.
		 *
		 * @since 1.0
		 *
		 * @param string        $open HTML markup for the opening outer wrapper element.
		 * @param Display_Posts $this
		 */
		$open = apply_filters( 'Easy_Plugins/Display_Posts/HTML/Wrap_Open', $open, $this );

		/**
		 * Filter the shortcode output's closing outer wrapper element.
		 *
		 * @deprecated
		 * @since 1.0
		 *
		 * @param string $wrapper_close HTML markup for the closing outer wrapper element.
		 * @param array  $untrusted     Original attributes passed to the shortcode.
		 * @param object $dps_listing   WP Query object
		 */
		$close = apply_filters_deprecated( 'display_posts_shortcode_wrapper_close', array( '</' . $wrapper_outer_tag . '>', $this->untrusted, $dps_listing ), '1.0', 'Easy_Plugins/Display_Posts/HTML/Wrap_Close' );

		/**
		 * Filter the shortcode output's closing outer wrapper element.
		 *
		 * @since 1.0
		 *
		 * @param string        $close HTML markup for the closing outer wrapper element.
		 * @param Display_Posts $this
		 */
		$close = apply_filters( 'Easy_Plugins/Display_Posts/HTML/Wrap_Close', $close, $this );

		$heading = posts_list_heading( $this->get_option( 'title', '' ), $this->untrusted );

		return $heading . $open . $inner . $close;
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
