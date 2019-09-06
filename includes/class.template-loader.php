<?php

namespace Easy_Plugins\Display_Posts\Template;

use Easy_Plugins\Display_Posts\Gamajo_Template_Loader;
use function Easy_Plugins\Display_Posts;

class Loader extends Gamajo_Template_Loader {

	public function __construct() {

		$this->filter_prefix = 'Easy_Plugins/Display_Posts/';
		$this->theme_template_directory = 'ezp-display-posts';
		$this->plugin_directory = Display_Posts()->getPath();
		$this->plugin_template_directory = 'includes/templates';
	}

	public function get_template_names( $slug, $name = NULL ) {

		return parent::get_template_file_names( $slug, $name );
	}
}
