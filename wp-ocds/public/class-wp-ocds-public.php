<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://guilles.website
 * @since      1.0.0
 *
 * @package    Wp_Ocds
 * @subpackage Wp_Ocds/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wp_Ocds
 * @subpackage Wp_Ocds/public
 * @author     Guillermo Ambrosio <yo@guilles.website>
 */
class Wp_Ocds_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private $loader;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $loader ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->loader      = $loader;

		$this->loader->add_filter("single_template", $this, "custom_single_view", 99);
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_scripts' );
	}

	public function custom_single_view($template) {
	    global $post;

	    // Is this a "my-custom-post-type" post?
	    if ($post->post_type == "ocdsrecord"){

	        //Your plugin path
	        $plugin_path = plugin_dir_path( __FILE__ );
			$template_name = 'templates/single_ocds_view.php';

	        return $plugin_path . $template_name;
	    }

	    //This is not my custom post type, do nothing with $template
	    return $template;
	}

	/**
	 * Register the JavaScript and CSS for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( "wp-ocds-public", plugin_dir_url( __FILE__ ) . 'css/wp-ocds-public.css', array(), $this->version, 'all' );
		wp_enqueue_script( "map-ocds-ocmp", plugin_dir_url( __FILE__ ) . 'js/mapa-ocds-ocmp.js', array(), $this->version, FALSE );
	}

}
