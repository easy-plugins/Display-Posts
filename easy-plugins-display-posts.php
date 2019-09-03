<?php

namespace Easy_Plugins\Display_Posts;

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

spl_autoload_register(
	function( $class ) {

		$registry = array(
			'Easy_Plugins\Display_Posts\Query'                   => 'includes/class.query.php',
			'Easy_Plugins\Display_Posts\Shortcode\Display_Posts' => 'includes/class.shortcode-display-posts.php',
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

add_shortcode( 'display-posts', array( 'Easy_Plugins\Display_Posts\Shortcode\Display_Posts', 'render' ) );
