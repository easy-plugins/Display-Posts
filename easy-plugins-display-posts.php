<?php

namespace Easy_Plugins;

/**
 * @package   Easy Plugins: Display Posts
 * @author    Steven A. Zahm
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @link      https://connections-pro.com
 * @copyright 2019 Steven A. Zahm
 *
 * Plugin Name:       Easy Plugins: Display Posts
 * Plugin URI:        https://connections-pro.com
 * Description:       Display a listing of posts using the [display-posts] shortcode. Based on the excellent Display Posts Shortcode plugin by Bill Erickson.
 * Version:           1.0
 * Author:            Steven A. Zahm
 * Author URI:        https://www.connections-pro.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       easy-plugins-display-posts
 * Domain Path:       /languages
 */

final class Display_Posts {

	const VERSION = '1.0';

	/**
	 * @var Display_Posts Stores the instance of this class.
	 *
	 * @since 1.0
	 */
	private static $instance;

	/**
	 * @var string The absolute path this this file.
	 *
	 * @since 1.0
	 */
	private $file = '';

	/**
	 * @var string The URL to the plugin's folder.
	 *
	 * @since 1.0
	 */
	private $url = '';

	/**
	 * @var string The absolute path to this plugin's folder.
	 *
	 * @since 1.0
	 */
	private $path = '';

	/**
	 * @var string The basename of the plugin.
	 *
	 * @since 1.0
	 */
	private $basename = '';

	/**
	 * A dummy constructor to prevent the class from being loaded more than once.
	 *
	 * @since 1.0
	 */
	protected function __construct() { /* Do nothing here */ }

	/**
	 * The main plugin instance.
	 *
	 * @since 1.0
	 *
	 * @return self
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {

			self::$instance = $self = new self;

			$self->file     = __FILE__;
			$self->url      = plugin_dir_url( $self->file );
			$self->path     = plugin_dir_path( $self->file );
			$self->basename = plugin_basename( $self->file );

			$self->includeDependencies();
			$self->hooks();
		}

		return self::$instance;

	}

	/**
	 * @since 1.0
	 */
	private function hooks() {

		add_shortcode( 'display-posts', array( 'Easy_Plugins\Display_Posts\Shortcode\Display_Posts', 'run' ) );
		//add_action( 'save_post', array( 'Easy_Plugins\Display_Posts\Shortcode\Display_Posts', 'clear_cache' ) );
		//add_action( 'created_term', array( 'Easy_Plugins\Display_Posts\Shortcode\Display_Posts', 'clear_cache' ) );
		//add_action( 'edit_term', array( 'Easy_Plugins\Display_Posts\Shortcode\Display_Posts', 'clear_cache' ) );
		//add_action( 'activated_plugin', array( 'Easy_Plugins\Display_Posts\Shortcode\Display_Posts', 'clear_cache' ) );
		//add_action( 'deactivated_plugin', array( 'Easy_Plugins\Display_Posts\Shortcode\Display_Posts', 'clear_cache' ) );
	}

	/**
	 * @since 1.0
	 */
	private function includeDependencies() {

		spl_autoload_register(
			function( $class ) {

				$registry = array(
					'Easy_Plugins\Display_Posts\Query'                   => 'includes/class.query.php',
					'Easy_Plugins\Display_Posts\Shortcode\Display_Posts' => 'includes/class.shortcode-display-posts.php',
					'Easy_Plugins\Display_Posts\Cache'                   => 'includes/class.cache.php',
					'Easy_Plugins\Display_Posts\Fragment'                => 'includes/class.cache.php',
					'Easy_Plugins\Display_Posts\Template\Post\Partials'  => 'includes/class.template-post-partials.php',
				);

				if ( ! isset( $registry[ $class ] ) ) {

					return;
				}

				$file = plugin_dir_path( __FILE__ ) . $registry[ $class ];

				// if the file exists, require it
				if ( file_exists( $file ) ) {

					require $file;

				} else {

					wp_die( esc_html( "The file attempting to be loaded at $file does not exist." ) );
				}
			}
		);

		require_once 'includes/inc.format.php';
		require_once 'includes/inc.functions.php';
		require_once 'includes/inc.template-partials.php';
	}

	/**
	 * @since 1.0
	 */
	public function getPath() {

		return $this->path;
	}

	/**
	 * @since 1.0
	 */
	public function getURL() {

		return $this->url;
	}
}

/**
 * @since 1.0
 *
 * @return Display_Posts
 */
function Display_Posts() {

	return Display_Posts::instance();
}

Display_Posts();
