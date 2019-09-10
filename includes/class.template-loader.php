<?php

namespace Easy_Plugins\Display_Posts\Template;

use Easy_Plugins\Display_Posts\Gamajo_Template_Loader;
use WP_Post;
use function Easy_Plugins\Display_Posts;

class Loader extends Gamajo_Template_Loader {

	public function __construct() {

		$this->filter_prefix = 'Easy_Plugins/Display_Posts/';
		$this->theme_template_directory = 'ezp-display-posts';
		$this->plugin_directory = Display_Posts()->getPath();
		$this->plugin_template_directory = 'includes/templates';

		add_filter( "{$this->filter_prefix}_template_paths", array( $this, 'add_content_dir_to_file_paths' ) );
	}

	/**
	 * @since 1.0
	 *
	 * @param string $slug
	 * @param string $name
	 *
	 * @return array
	 */
	public function get_template_names( string $slug, $name = NULL ) : array {

		return parent::get_template_file_names( $slug, $name );
	}

	/**
	 * @since 1.0
	 *
	 * @param array $file_paths
	 *
	 * @return array
	 */
	public function add_content_dir_to_file_paths( array $file_paths ) : array {

		$theme_directory = trailingslashit( $this->theme_template_directory );
		$content_path    = trailingslashit( WP_CONTENT_DIR ) . $theme_directory;

		if ( ! in_array( $content_path, $file_paths ) ) {

			$file_paths[50] = $content_path;
		}

		return $file_paths;
	}

	/**
	 * @since 1.0
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	public static function get_template_path( string $slug ) : string {

		/**
		 * @var WP_Post $post
		 */
		global $post;

		$loader = new static();
		$paths  = array();

		$names = array( $slug );

		if ( $post instanceof WP_Post ) {

			array_unshift( $names, "{$slug}-{$post->post_name}", "{$slug}-{$post->ID}" );
		}

		foreach ( $names as $name ) {

			$paths = array_merge( $paths, $loader->get_template_names( $name ) );
		}

		return $loader->locate_template( $paths );
	}
}
